<?php

/**
 * Vehicle report form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function vs_vehiclereport_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_render_vehiclereport_form(PluginVehicleschedulerVehiclereport $report): void
{
    $entityId = (int) ($report->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0));
    $t = static fn(string $text): string => __($text, 'vehiclescheduler');
    ?>
    <div class="vs-vehiclereport-wrap" data-vs-vehiclereport-form>
        <div class="vs-vehiclereport-surface">
            <div class="vs-vehiclereport-card">
                <div class="vs-vehiclereport-head">
                    <div>
                        <h3 class="vs-vehiclereport-title"><i class="ti ti-file-report"></i> <?= vs_vehiclereport_render_escape($t('Vehicle report')) ?></h3>
                        <div class="vs-vehiclereport-sub"><?= vs_vehiclereport_render_escape($t('Structured record of problems, observations, and events to support fleet management.')) ?></div>
                    </div>
                    <div class="vs-vehiclereport-pill"><span class="dot"></span> <?= vs_vehiclereport_render_escape($t('Reports')) ?></div>
                </div>

                <div class="vs-form-feedback" data-vehiclereport-validation hidden></div>

                <div class="vs-vehiclereport-grid">
                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Vehicle')) ?> <span class="red">*</span></div>
                        <?php
                        PluginVehicleschedulerVehicle::dropdown([
                            'name'   => 'plugin_vehiclescheduler_vehicles_id',
                            'value'  => (int) ($report->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
                            'entity' => $entityId,
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Report type')) ?> <span class="red">*</span></div>
                        <?php
                        Dropdown::showFromArray('report_type', PluginVehicleschedulerVehiclereport::getAllTypes(), [
                            'value' => (int) ($report->fields['report_type'] ?? PluginVehicleschedulerVehiclereport::TYPE_OBSERVATION),
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Reported by')) ?></div>
                        <?php
                        User::dropdown([
                            'name'  => 'users_id',
                            'value' => (int) ($report->fields['users_id'] ?? Session::getLoginUserID()),
                            'right' => 'all',
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Department / sector')) ?> <span class="vs-vehiclereport-hint-inline"><?= vs_vehiclereport_render_escape($t('optional')) ?></span></div>
                        <?= Html::input('department', [
                            'value'       => $report->fields['department'] ?? '',
                            'size'        => 40,
                            'placeholder' => $t('Example: Operations'),
                        ]) ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Contact phone')) ?> <span class="vs-vehiclereport-hint-inline"><?= vs_vehiclereport_render_escape($t('optional')) ?></span></div>
                        <?= Html::input('contact_phone', [
                            'value'       => $report->fields['contact_phone'] ?? '',
                            'size'        => 20,
                            'placeholder' => '(00) 00000-0000',
                        ]) ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Report date')) ?> <span class="red">*</span></div>
                        <?php
                        Html::showDateTimeField('report_date', [
                            'value' => $report->fields['report_date'] ?? date('Y-m-d H:i:s'),
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field vs-vehiclereport-field--full">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Description')) ?> <span class="red">*</span></div>
                        <textarea name="description" rows="6" placeholder="<?= vs_vehiclereport_render_escape($t('Describe the problem, observation, or situation in detail')) ?>"><?= vs_vehiclereport_render_escape($report->fields['description'] ?? '') ?></textarea>
                    </div>

                    <div class="vs-vehiclereport-field vs-vehiclereport-field--full">
                        <div class="vs-vehiclereport-label"><?= vs_vehiclereport_render_escape($t('Additional comments')) ?> <span class="vs-vehiclereport-hint-inline"><?= vs_vehiclereport_render_escape($t('optional')) ?></span></div>
                        <textarea name="comment" rows="3"><?= vs_vehiclereport_render_escape($report->fields['comment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
