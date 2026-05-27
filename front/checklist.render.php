<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_vehiclescheduler_render_checklist_form(
    PluginVehicleschedulerChecklist $checklist,
    int $checklistId,
    string $rootDoc
): void {
    $t = static fn(string $message): string => __($message, 'vehiclescheduler');
    $fields = $checklist->fields;
    $types = PluginVehicleschedulerChecklist::getChecklistTypes();
    $canEdit = $checklistId > 0
        ? Session::haveRight('plugin_vehiclescheduler', UPDATE)
        : Session::haveRight('plugin_vehiclescheduler', CREATE);

    $formAction = plugin_vehiclescheduler_get_front_url('checklist.form.php');
    $listUrl = plugin_vehiclescheduler_get_front_url('checklist.php');

    echo "<div class='vs-checklist-form-card'>";
    echo "<div class='vs-checklist-list-header'>";
    echo '<div>';
    echo '<h1><i class="ti ti-checkbox"></i> ' . plugin_vehiclescheduler_escape($t('Checklist template')) . '</h1>';
    echo '<p class="vs-checklist-list-subtitle">' . plugin_vehiclescheduler_escape($t('Configure name, type, status, and template items.')) . '</p>';
    echo '</div>';
    echo "<a href='" . plugin_vehiclescheduler_escape($listUrl) . "' class='vs-checklist-list-create'>";
    echo '<i class="ti ti-arrow-left"></i>';
    echo '<span>' . plugin_vehiclescheduler_escape($t('Back to checklists')) . '</span>';
    echo '</a>';
    echo '</div>';

    echo "<div class='vs-checklist-form-content'>";
    echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

    if ($checklistId > 0) {
        echo Html::hidden('id', ['value' => $checklistId]);
    }

    echo "<div class='vs-checklist-form-grid'>";

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-name'>" . plugin_vehiclescheduler_escape($t('Name')) . " <span class='red'>*</span></label>";
    echo "<input class='vs-checklist-form-input' type='text' id='vs-checklist-name' name='name' value='"
        . plugin_vehiclescheduler_escape((string) ($fields['name'] ?? '')) . "' maxlength='255' required>";
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-type'>" . plugin_vehiclescheduler_escape($t('Type')) . " <span class='red'>*</span></label>";
    echo "<select class='vs-checklist-form-select' id='vs-checklist-type' name='checklist_type'>";

    foreach ($types as $typeId => $typeLabel) {
        $selected = ((int) ($fields['checklist_type'] ?? PluginVehicleschedulerChecklist::TYPE_DEPARTURE) === (int) $typeId)
            ? ' selected'
            : '';

        echo "<option value='" . (int) $typeId . "'" . $selected . '>'
            . plugin_vehiclescheduler_escape($typeLabel)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-active'>" . plugin_vehiclescheduler_escape($t('Active')) . '</label>';
    echo "<select class='vs-checklist-form-select' id='vs-checklist-active' name='is_active'>";
    echo plugin_vehiclescheduler_render_yes_no_options((int) ($fields['is_active'] ?? 1));
    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-mandatory'>" . plugin_vehiclescheduler_escape($t('Mandatory')) . '</label>';
    echo "<select class='vs-checklist-form-select' id='vs-checklist-mandatory' name='is_mandatory'>";
    echo plugin_vehiclescheduler_render_yes_no_options((int) ($fields['is_mandatory'] ?? 1));
    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field vs-checklist-form-field--full'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-description'>" . plugin_vehiclescheduler_escape($t('Description')) . '</label>';
    echo "<textarea class='vs-checklist-form-textarea' id='vs-checklist-description' name='description' rows='4'>"
        . plugin_vehiclescheduler_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    if ($canEdit) {
        echo "<div class='vs-checklist-form-actions'>";

        if ($checklistId > 0) {
            echo "<button type='submit' name='update' class='vs-checklist-form-button vs-checklist-form-button--primary'>" . plugin_vehiclescheduler_escape($t('Save')) . '</button>';
            echo "<button type='submit' name='delete' class='vs-checklist-form-button vs-checklist-form-button--danger' data-confirm-message='" . plugin_vehiclescheduler_escape($t('Delete this template?')) . "'>" . plugin_vehiclescheduler_escape($t('Delete')) . '</button>';
        } else {
            echo "<button type='submit' name='add' class='vs-checklist-form-button vs-checklist-form-button--primary'>" . plugin_vehiclescheduler_escape($t('Create template')) . '</button>';
        }

        echo "<a href='" . plugin_vehiclescheduler_escape($listUrl) . "' class='vs-checklist-form-button vs-checklist-form-button--secondary'>" . plugin_vehiclescheduler_escape($t('Cancel')) . '</a>';
        echo '</div>';
    }

    echo '</form>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_render_checklist_items_panel(
    int $checklistId,
    array $items,
    int $editingId,
    string $rootDoc,
    bool $canEdit
): void {
    $t = static fn(string $message): string => __($message, 'vehiclescheduler');
    $formAction = plugin_vehiclescheduler_get_front_url('checklistitem.form.php');
    $types = PluginVehicleschedulerChecklistitem::getItemTypes();

    echo "<div class='vs-checklist-form-card vs-checklist-items-panel'>";
    echo "<div class='vs-checklist-items-panel__header'>";
    echo '<h2>' . plugin_vehiclescheduler_escape($t('Checklist items')) . '</h2>';
    echo '<p>' . plugin_vehiclescheduler_escape($t('Build the required verification sequence for the template.')) . '</p>';
    echo '</div>';
    echo "<div class='vs-checklist-items'>";

    if ($canEdit) {
        echo "<div class='vs-checklist-items__editor'>";
        echo "<h3 class='vs-checklist-items__title'>" . plugin_vehiclescheduler_escape($t('Add item')) . '</h3>';
        echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
        echo Html::hidden('plugin_vehiclescheduler_checklists_id', ['value' => $checklistId]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<div class='vs-checklist-items__grid'>";
        echo "<div class='vs-checklist-items__field'>";
        echo "<label for='vs-checklist-item-description'>" . plugin_vehiclescheduler_escape($t('Description')) . " <span class='red'>*</span></label>";
        echo "<input type='text' id='vs-checklist-item-description' name='description' placeholder='" . plugin_vehiclescheduler_escape($t('Example: Is the vehicle clean?')) . "' maxlength='255' required>";
        echo '</div>';
        echo "<div class='vs-checklist-items__field'>";
        echo "<label for='vs-checklist-item-type'>" . plugin_vehiclescheduler_escape($t('Type')) . '</label>';
        echo "<select id='vs-checklist-item-type' name='item_type' class='vs-checklist-items__select'>";

        foreach ($types as $typeId => $typeLabel) {
            echo "<option value='" . (int) $typeId . "'>" . plugin_vehiclescheduler_escape($typeLabel) . '</option>';
        }

        echo '</select>';
        echo '</div>';
        echo "<div class='vs-checklist-items__field'>";
        echo "<button type='submit' name='add' class='vs-checklist-items__button vs-checklist-items__button--primary'>" . plugin_vehiclescheduler_escape($t('Add')) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    if (empty($items)) {
        echo "<div class='vs-checklist-items__empty'>";
        echo "<div class='vs-checklist-items__empty-icon'>+</div>";
        echo "<div class='vs-checklist-items__empty-title'>" . plugin_vehiclescheduler_escape($t('No item added.')) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        return;
    }

    echo "<div class='vs-checklist-items__list'>";

    foreach ($items as $item) {
        $itemId = (int) ($item['id'] ?? 0);
        $description = plugin_vehiclescheduler_escape((string) ($item['description'] ?? ''));
        $typeLabel = plugin_vehiclescheduler_escape($types[(int) ($item['item_type'] ?? PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX)] ?? '-');
        $editUrl = plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $checklistId . '&edit_item=' . $itemId;
        $deleteUrl = $formAction . '?id=' . $itemId . '&delete=1';

        if ($canEdit && $editingId === $itemId) {
            echo "<div class='vs-checklist-items__card--editing'>";
            echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
            echo Html::hidden('id', ['value' => $itemId]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<div class='vs-checklist-items__grid vs-checklist-items__grid--editing'>";
            echo "<div class='vs-checklist-items__field'>";
            echo "<label for='vs-checklist-item-edit-" . $itemId . "'>" . plugin_vehiclescheduler_escape($t('Description')) . '</label>';
            echo "<input type='text' id='vs-checklist-item-edit-" . $itemId . "' name='description' value='" . $description . "' maxlength='255' required>";
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<label for='vs-checklist-item-type-edit-" . $itemId . "'>" . plugin_vehiclescheduler_escape($t('Type')) . '</label>';
            echo "<select id='vs-checklist-item-type-edit-" . $itemId . "' name='item_type' class='vs-checklist-items__select'>";

            foreach ($types as $typeId => $label) {
                $selected = ((int) ($item['item_type'] ?? PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX) === (int) $typeId)
                    ? ' selected'
                    : '';

                echo "<option value='" . (int) $typeId . "'" . $selected . '>'
                    . plugin_vehiclescheduler_escape($label)
                    . '</option>';
            }

            echo '</select>';
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<button type='submit' name='update' class='vs-checklist-items__button vs-checklist-items__button--primary'>" . plugin_vehiclescheduler_escape($t('Save')) . '</button>';
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<a href='" . plugin_vehiclescheduler_escape(plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $checklistId) . "' class='vs-checklist-items__link vs-checklist-items__link--secondary'>" . plugin_vehiclescheduler_escape($t('Cancel')) . '</a>';
            echo '</div>';
            echo '</div>';
            echo '</form>';
            echo '</div>';

            continue;
        }

        echo "<div class='vs-checklist-items__card'>";
        echo "<div class='vs-checklist-items__content'>";
        echo "<div class='vs-checklist-items__description'>" . $description . '</div>';
        echo "<span class='vs-checklist-items__badge'>" . $typeLabel . '</span>';
        echo '</div>';

        if ($canEdit) {
            echo "<div class='vs-checklist-items__actions'>";
            echo "<a href='" . plugin_vehiclescheduler_escape($editUrl) . "' class='vs-checklist-items__link vs-checklist-items__link--edit'>" . plugin_vehiclescheduler_escape($t('Edit')) . '</a>';
            echo "<a href='" . plugin_vehiclescheduler_escape($deleteUrl) . "' class='vs-checklist-items__link vs-checklist-items__link--danger' data-confirm-message='" . plugin_vehiclescheduler_escape($t('Delete this item?')) . "'>" . plugin_vehiclescheduler_escape($t('Delete')) . '</a>';
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_render_yes_no_options(int $selected): string
{
    $html = '';

    foreach ([1 => __('Yes', 'vehiclescheduler'), 0 => __('No', 'vehiclescheduler')] as $value => $label) {
        $isSelected = $selected === $value ? ' selected' : '';
        $html .= "<option value='" . $value . "'" . $isSelected . '>'
            . plugin_vehiclescheduler_escape($label)
            . '</option>';
    }

    return $html;
}

function plugin_vehiclescheduler_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
