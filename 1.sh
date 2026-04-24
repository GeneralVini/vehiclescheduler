#!/usr/bin/env bash
set -euo pipefail

STAMP="$(date +%Y%m%d%H%M%S)"

write_future_front() {
    local file="$1"
    local label="$2"
    local context="${3-}"

    if [ -f "$file" ]; then
        cp "$file" "${file}.bak-future-plan-${STAMP}"
    fi

    if [ -n "$context" ]; then
        cat > "$file" <<PHP
<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

plugin_vehiclescheduler_redirect_future_plan('${label}', '${context}');
exit;
PHP
    else
        cat > "$file" <<PHP
<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

plugin_vehiclescheduler_redirect_future_plan('${label}');
exit;
PHP
    fi
}

write_future_front "front/incident.php" "Incidentes"
write_future_front "front/incident.form.php" "Incidentes" "formulário"

write_future_front "front/maintenance.php" "Manutenções"
write_future_front "front/maintenance.form.php" "Manutenções" "formulário"

write_future_front "front/checklist.php" "Checklist"
write_future_front "front/checklist.form.php" "Checklist" "formulário"
write_future_front "front/checklistresponse.form.php" "Checklist" "resposta operacional"

php -l front/incident.php
php -l front/incident.form.php
php -l front/maintenance.php
php -l front/maintenance.form.php
php -l front/checklist.php
php -l front/checklist.form.php
php -l front/checklistresponse.form.php

echo "OK: fronts marcados como Planos Futuros."