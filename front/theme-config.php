<?php

/**
 * Theme configuration page.
 */

include_once(__DIR__ . '/../src/Bootstrap/common.php');

Session::checkRight('config', UPDATE);

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$t = static function (string $message): string {
    return __($message, 'vehiclescheduler');
};

if (isset($_POST['save_theme'])) {
    $themeCode = PluginVehicleschedulerInput::enum(
        $_POST,
        'theme_code',
        array_keys(PluginVehicleschedulerTheme::getAllThemes()),
        PluginVehicleschedulerTheme::THEME_BLUE
    );

    if (PluginVehicleschedulerTheme::saveTheme($themeCode)) {
        plugin_vehiclescheduler_flash_success($t('Theme saved successfully.'));
    }

    Html::redirect($_SERVER['PHP_SELF']);
}

$currentTheme = PluginVehicleschedulerTheme::getCurrentTheme();
$allThemes    = PluginVehicleschedulerTheme::getAllThemes();

Html::header($t('Theme settings'), $_SERVER['PHP_SELF'], 'config', 'plugins');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<div class="vs-theme-config-page">
    <section class="vs-theme-config-hero">
        <div>
            <p class="vs-theme-config-hero__eyebrow"><?= $h($t('Visual customization')) ?></p>
            <h1><?= $h($t('Theme settings')) ?></h1>
            <p><?= $h($t('Choose a palette that improves operational readability while preserving light and dark contrast.')) ?></p>
        </div>
        <div class="vs-theme-config-hero__tip">
            <strong><?= $h($t('Tip')) ?></strong>
            <span><?= $h($t('The light/dark toggle remains available on plugin screens.')) ?></span>
        </div>
    </section>

    <form method="post" action="<?= $h($_SERVER['PHP_SELF']) ?>" class="vs-theme-config-form">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

        <section class="vs-theme-grid">
            <?php foreach ($allThemes as $code => $theme): ?>
                <?php $checked = $code === $currentTheme ? ' checked' : ''; ?>
                <label class="vs-theme-card">
                    <div class="vs-theme-card__selector">
                        <input type="radio" name="theme_code" value="<?= $h($code) ?>"<?= $checked ?>>
                        <div>
                            <strong><?= $h($theme['name']) ?></strong>
                            <span><?= $h($code) ?></span>
                        </div>
                    </div>
                    <div class="vs-theme-card__previews">
                        <div class="vs-theme-card__preview vs-theme-card__preview--<?= $h($code) ?>-light">
                            <span>☀ <?= $h($t('Light')) ?></span>
                        </div>
                        <div class="vs-theme-card__preview vs-theme-card__preview--<?= $h($code) ?>-dark">
                            <span>🌙 <?= $h($t('Dark')) ?></span>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </section>

        <footer class="vs-theme-config-form__footer">
            <button type="submit" name="save_theme" class="vs-theme-config-save"><?= $h($t('Save theme')) ?></button>
        </footer>
    </form>
</div>
<?php Html::footer(); ?>
