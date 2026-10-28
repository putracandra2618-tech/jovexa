<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\OrderWorkflow;
use App\Support\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_status_has_a_label_and_badge_class(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertContains($status->badgeClass(), ['pending', 'success', 'failed']);
        }
    }

    public function test_payment_success_is_limited_to_paid(): void
    {
        $this->assertTrue(PaymentStatus::Paid->isSuccessful());
        $this->assertFalse(PaymentStatus::Pending->isSuccessful());
        $this->assertFalse(PaymentStatus::Failed->isSuccessful());
        $this->assertFalse(PaymentStatus::Expired->isSuccessful());
    }

    public function test_happy_path_follows_the_expected_sequence(): void
    {
        $this->assertSame(OrderStatus::Paid, OrderStatus::PendingPayment->next());
        $this->assertSame(OrderStatus::WaitingVerification, OrderStatus::Paid->next());
        $this->assertSame(OrderStatus::Queued, OrderStatus::WaitingVerification->next());
        $this->assertSame(OrderStatus::InProgress, OrderStatus::Queued->next());
        $this->assertSame(OrderStatus::Completed, OrderStatus::InProgress->next());
        $this->assertSame(OrderStatus::Delivered, OrderStatus::Completed->next());
        $this->assertNull(OrderStatus::Delivered->next());
    }

    public function test_clarification_returns_the_order_to_the_verification_queue(): void
    {
        $this->assertTrue(OrderStatus::WaitingVerification->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertTrue(OrderStatus::WaitingCustomer->canTransitionTo(OrderStatus::WaitingVerification));
    }

    public function test_verified_orders_enter_the_worker_queue(): void
    {
        $this->assertTrue(OrderStatus::WaitingVerification->canTransitionTo(OrderStatus::Queued));
        $this->assertTrue(OrderStatus::Queued->canTransitionTo(OrderStatus::InProgress));
        $this->assertFalse(OrderStatus::WaitingVerification->canTransitionTo(OrderStatus::InProgress));
    }

    public function test_customer_can_return_an_assigned_order_to_work(): void
    {
        $this->assertTrue(OrderStatus::WaitingCustomer->canTransitionTo(OrderStatus::InProgress));
    }

    public function test_admin_may_ask_the_customer_a_question_while_the_order_is_handled(): void
    {
        $this->assertTrue(OrderStatus::Paid->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertTrue(OrderStatus::Queued->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertTrue(OrderStatus::InProgress->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertTrue(OrderStatus::Revision->canTransitionTo(OrderStatus::WaitingCustomer));

        $this->assertFalse(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::WaitingCustomer));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::WaitingCustomer));
    }

    public function test_revision_is_only_allowed_from_working_or_completed_orders(): void
    {
        $this->assertTrue(OrderStatus::InProgress->canTransitionTo(OrderStatus::Revision));
        $this->assertTrue(OrderStatus::Completed->canTransitionTo(OrderStatus::Revision));
        $this->assertFalse(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Revision));
        $this->assertFalse(OrderStatus::WaitingVerification->canTransitionTo(OrderStatus::Revision));
    }

    public function test_cancellation_is_blocked_after_delivery(): void
    {
        $this->assertTrue(OrderStatus::InProgress->canTransitionTo(OrderStatus::Cancelled));
        $this->assertTrue(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_an_order_cannot_transition_to_its_own_status(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status));
        }
    }

    public function test_skipping_a_step_is_not_allowed(): void
    {
        $this->assertFalse(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::InProgress));
        $this->assertFalse(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::WaitingVerification->canTransitionTo(OrderStatus::InProgress));
        $this->assertFalse(OrderStatus::InProgress->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_deliverables_only_unlock_after_the_work_is_done(): void
    {
        $this->assertFalse(OrderStatus::InProgress->hasDeliverables());
        $this->assertFalse(OrderStatus::Revision->hasDeliverables());
        $this->assertTrue(OrderStatus::Completed->hasDeliverables());
        $this->assertTrue(OrderStatus::Delivered->hasDeliverables());
    }

    public function test_final_states_are_terminal(): void
    {
        $this->assertTrue(OrderStatus::Delivered->isFinal());
        $this->assertTrue(OrderStatus::Cancelled->isFinal());
        $this->assertFalse(OrderStatus::Completed->isFinal());
    }

    public function test_generated_order_numbers_are_prefixed_and_unique(): void
    {
        $generator = app(OrderNumberGenerator::class);

        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertStringStartsWith('JVX-', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_customer_step_timeline_collapses_internal_statuses(): void
    {
        $steps = OrderStatus::customerSteps();
        $this->assertCount(6, $steps);
        $this->assertSame('Bayar', $steps[0]['label']);
        $this->assertContains(OrderStatus::PendingPayment, $steps[0]['statuses']);
        $this->assertContains(OrderStatus::Expired, $steps[0]['statuses']);
        $this->assertSame('Dikerjakan', $steps[3]['label']);
        $this->assertContains(OrderStatus::Revision, $steps[3]['statuses']);

        $this->assertSame(0, OrderStatus::PendingPayment->customerStepIndex());
        $this->assertSame(0, OrderStatus::Expired->customerStepIndex());
        $this->assertSame(3, OrderStatus::Revision->customerStepIndex());
        $this->assertSame(-1, OrderStatus::Cancelled->customerStepIndex());
    }

    public function test_a_reserved_status_cannot_be_set_without_gateway_confirmation(): void
    {
        $workflow = app(OrderWorkflow::class);

        foreach (OrderStatus::GATEWAY_ONLY as $reserved) {
            $order = Order::factory()->waitingVerification()->create();

            $this->expectException(InvalidArgumentException::class);

            try {
                $workflow->transitionTo($order, $reserved);
            } finally {
                $this->assertSame(OrderStatus::WaitingVerification, $order->fresh()->status);
                $this->assertSame(0, $order->statusHistory()->count());
            }
        }
    }

    public function test_a_reserved_status_is_allowed_for_the_gateway_callback(): void
    {
        $order = Order::factory()->waitingVerification()->create();

        $updated = app(OrderWorkflow::class)->transitionTo(
            $order,
            OrderStatus::WaitingCustomer,
            note: 'Dikonfirmasi webhook.',
            confirmedByGateway: true,
        );

        $this->assertSame(OrderStatus::WaitingCustomer, $updated->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->getKey(),
            'from_order_status_id' => OrderStatusLookup::idFor(OrderStatus::WaitingVerification->value),
            'to_order_status_id' => OrderStatusLookup::idFor(OrderStatus::WaitingCustomer->value),
        ]);
    }

    public function test_an_order_cannot_be_delivered_before_a_result_file_exists(): void
    {
        $order = Order::factory()->completed()->create();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(OrderWorkflow::class)->transitionTo($order, OrderStatus::Delivered);
        } finally {
            $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        }
    }

    public function test_a_requirement_file_does_not_count_as_a_deliverable(): void
    {
        $order = Order::factory()->completed()->create();
        OrderFile::factory()->requirement()->for($order)->create();

        $this->expectException(InvalidArgumentException::class);

        app(OrderWorkflow::class)->transitionTo($order, OrderStatus::Delivered);
    }

    public function test_an_order_with_a_result_file_can_be_delivered(): void
    {
        $order = Order::factory()->completed()->create();
        OrderFile::factory()->result()->for($order)->create();

        app(OrderWorkflow::class)->transitionTo($order, OrderStatus::Delivered);

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }
}
