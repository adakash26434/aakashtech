<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_public_service'])) {
    verify_csrf();
    $slug = isset($_POST['service_slug']) ? (string) $_POST['service_slug'] : '';
    $saveError = billing_save_public_service(
        $conn,
        $slug,
        isset($_POST['title']) ? $_POST['title'] : '',
        isset($_POST['description']) ? $_POST['description'] : '',
        isset($_POST['features']) ? $_POST['features'] : ''
    );
    if ($saveError !== '') {
        $err = $saveError;
    } else {
        $msg = 'Public service text saved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_poster'])) {
    verify_csrf();
    $slug = isset($_POST['poster_slug']) ? (string) $_POST['poster_slug'] : '';
    $known = billing_service_definitions();
    if (!isset($known[$slug])) {
        $err = 'That service is not on the public site.';
    } else {
        $poster = site_store_poster(isset($_FILES['poster']) ? $_FILES['poster'] : array(), $slug);
        if (!$poster['ok']) {
            $err = $poster['error'];
        } else {
            if (!empty($_POST['remove_poster'])) {
                $dir = dirname(__DIR__) . '/uploads';
                foreach (glob($dir . '/service-poster-' . $slug . '.*') as $old) {
                    if (is_file($old)) {
                        unlink($old);
                    }
                }
                billing_set_setting($conn, 'service_poster_' . $slug, '');
            } elseif ($poster['path'] !== null) {
                billing_set_setting($conn, 'service_poster_' . $slug, $poster['path']);
            }
            $msg = 'Service photo saved.';
        }
    }
}

$posterServices = billing_service_definitions();
$serviceOverrides = billing_catalog_overrides($conn);
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Services</h1>
        <p class="text-slate-500 text-sm">Edit the title, summary, and tags shown on the public site. Prices stay under Billing.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="dash-panel mb-6">
    <div class="p-5 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h3 class="font-heading font-semibold text-white">Checkout prices</h3>
            <p class="text-slate-500 text-xs mt-1">The public cards and client shop use the prices saved in Billing.</p>
        </div>
        <a href="billing.php" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Open billing</a>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">See rates photos</h3></div>
    <div class="p-5 space-y-5">
        <p class="text-slate-500 text-sm">Upload a photo or poster for a service. It appears at the top of that service page. Leave it empty and nothing is shown.</p>
        <?php foreach ($posterServices as $slug => $service): ?>
            <?php $posterPreview = site_service_poster($conn, $slug); ?>
            <form method="POST" action="" enctype="multipart/form-data" class="grid sm:grid-cols-[140px_1fr] gap-4 items-center border-t border-slate-800 pt-5 first:border-0 first:pt-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="poster_slug" value="<?= e($slug) ?>">
                <div>
                    <?php if ($posterPreview !== ''): ?>
                        <img src="<?= e($posterPreview) ?>" alt="" class="w-full max-h-24 object-contain bg-white rounded-xl p-1">
                    <?php else: ?>
                        <div class="h-20 rounded-xl border border-dashed border-slate-700 text-slate-500 text-xs flex items-center justify-center">No photo</div>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-white text-sm font-medium mb-2"><?= e($service['title']) ?></p>
                    <input type="file" name="poster" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                    <?php if ($posterPreview !== ''): ?>
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="remove_poster" value="1"> Remove the current photo
                        </label>
                    <?php endif; ?>
                    <button type="submit" name="save_poster" class="mt-3 px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save photo</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Public service text</h3></div>
    <div class="p-5 space-y-6">
        <p class="text-slate-500 text-sm">These words appear on the homepage cards and as the heading on each service page. Tags are separated by commas.</p>
        <?php foreach ($posterServices as $slug => $service): ?>
            <?php $view = billing_saved_service_view($conn, $slug, $serviceOverrides); ?>
            <form method="POST" action="" class="space-y-3 border-t border-slate-800 pt-5 first:border-0 first:pt-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_slug" value="<?= e($slug) ?>">
                <p class="text-white text-sm font-medium"><?= e($view['title']) ?></p>
                <input type="text" name="title" maxlength="120" required class="form-input" value="<?= e($view['title']) ?>">
                <textarea name="description" maxlength="500" rows="3" class="form-input"><?= e($view['summary']) ?></textarea>
                <input type="text" name="features" maxlength="300" class="form-input" value="<?= e(implode(', ', $view['tags'])) ?>">
                <button type="submit" name="save_public_service" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save text</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
