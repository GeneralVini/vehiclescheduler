<?php

/**
 * Requester self-service portal for fleet actions.
 *
 * Scope:
 * - validates requester portal ACL;
 * - renders the fleet self-service landing page;
 * - exposes the active booking entry point.
 */

include_once __DIR__ . '/../src/Bootstrap/common.php';
include_once __DIR__ . '/../src/Bootstrap/ui-helpers.php';

Session::checkRight('plugin_vehiclescheduler_portal', READ);

$root_doc = plugin_vehiclescheduler_get_root_doc();

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

$booking_form_url = $root_doc . '/Form/Render/3';

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

Html::header(__('Fleet vehicles', 'vehiclescheduler'), $self, 'helpdesk');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

?>
<div class="vs-page vs-page-requester">
    <div class="vs-requester-wrap">
        <section class="vs-requester-hero">
            <div class="vs-requester-pill">
                <i class="ti ti-car-suv"></i>
                <span><?= $escape(__('Fleet portal', 'vehiclescheduler')) ?></span>
            </div>

            <h1 class="vs-requester-title">
                <i class="ti ti-steering-wheel"></i>
                <span><?= $escape(__('Fleet vehicles', 'vehiclescheduler')) ?></span>
            </h1>

            <p class="vs-requester-subtitle">
                <?= $escape(__('Request a vehicle in a simple way and follow the evolution of the fleet service portal.', 'vehiclescheduler')) ?>
            </p>
        </section>

        <section class="vs-requester-grid">
            <article class="vs-requester-card">
                <div class="vs-requester-icon">
                    <i class="ti ti-calendar-plus"></i>
                </div>

                <h3><?= $escape(__('Schedule vehicle', 'vehiclescheduler')) ?></h3>

                <p>
                    <?= $escape(__('Open the request form to reserve a vehicle and start the service flow.', 'vehiclescheduler')) ?>
                </p>

                <a class="vs-requester-btn vs-requester-btn--primary"
                    href="<?php echo $escape($booking_form_url); ?>">
                    <i class="ti ti-arrow-right"></i>
                    <span><?= $escape(__('Open form', 'vehiclescheduler')) ?></span>
                </a>
            </article>

            <article class="vs-requester-card is-disabled">
                <div class="vs-requester-icon">
                    <i class="ti ti-calendar-event"></i>
                </div>

                <h3><?= $escape(__('My reservations', 'vehiclescheduler')) ?></h3>

                <p>
                    <?= $escape(__('Check opened requests, follow statuses, and view your reservation history.', 'vehiclescheduler')) ?>
                </p>

                <span class="vs-requester-btn vs-requester-btn--secondary" aria-disabled="true">
                    <i class="ti ti-clock"></i>
                    <span><?= $escape(__('Coming soon', 'vehiclescheduler')) ?></span>
                </span>
            </article>

            <article class="vs-requester-card">
                <div class="vs-requester-icon">
                    <i class="ti ti-alert-triangle"></i>
                </div>

                <h3><?= $escape(__('Report claim', 'vehiclescheduler')) ?></h3>

                <p>
                    <?= $escape(__('Register accidents, damage, and events related to vehicle use.', 'vehiclescheduler')) ?>
                </p>

                <a class="vs-requester-btn vs-requester-btn--primary"
                    href="<?php echo $escape(plugin_vehiclescheduler_get_front_url('incident.form.php')); ?>">
                    <i class="ti ti-arrow-right"></i>
                    <span><?= $escape(__('Open form', 'vehiclescheduler')) ?></span>
                </a>
            </article>
        </section>

        <div class="vs-requester-note">
            <strong><?= $escape(__('Note:', 'vehiclescheduler')) ?></strong>
            <?= $escape(__('Use this address as the destination for the Fleet vehicles card on the self-service home.', 'vehiclescheduler')) ?>
            <?= $escape(__('In this first stage, the active flow is scheduling.', 'vehiclescheduler')) ?>
        </div>
    </div>
</div>

<?php Html::footer(); ?>
