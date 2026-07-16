<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$cars = cars_all(false);
$carId = (string)($_GET['car_id'] ?? $_POST['car_id'] ?? ($cars[0]['id'] ?? ''));
$car = $carId ? car_by_id($carId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'upload');

    try {
        if ($action === 'upload' && $car) {
            $files = $_FILES['media_files'] ?? null;
            if (!$files) throw new RuntimeException('Choose one or more images or videos.');

            $count = 0;
            $total = is_array($files['name']) ? count($files['name']) : 0;
            for ($i = 0; $i < $total; $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $file = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $upload = safe_upload($file, $car['slug']);
                $existing = media_for_car($carId);
                $defaultPoster = '';
                if ($upload['type'] === 'video') {
                    foreach ($existing as $existingMedia) {
                        if (($existingMedia['type'] ?? '') === 'image') { $defaultPoster = (string)$existingMedia['path']; break; }
                    }
                }
                save_media([
                    'id' => '', 'car_id' => $carId, 'type' => $upload['type'], 'path' => $upload['path'],
                    'poster' => $defaultPoster, 'title' => pathinfo((string)$file['name'], PATHINFO_FILENAME),
                    'alt' => $car['make'].' '.$car['model'], 'sort_order' => count($existing) + 1, 'created_at' => '',
                ]);
                $count++;
            }
            activity('Media uploaded', $car['model'].' — '.$count.' file(s)');
            flash('success', $count.' media file(s) uploaded.');
        }

        if ($action === 'add_youtube' && $car) {
            $videoUrl = trim((string)($_POST['video_url'] ?? ''));
            if (youtube_video_id($videoUrl) === '') {
                throw new RuntimeException('Enter a valid YouTube video URL.');
            }
            $existing = media_for_car($carId);
            $poster = trim((string)($_POST['poster'] ?? ''));
            if ($poster === '') {
                foreach ($existing as $existingMedia) {
                    if (($existingMedia['type'] ?? '') === 'image') { $poster = (string)$existingMedia['path']; break; }
                }
            }
            $title = trim((string)($_POST['video_title'] ?? ''));
            save_media([
                'id' => '', 'car_id' => $carId, 'type' => 'youtube', 'path' => $videoUrl,
                'poster' => $poster,
                'title' => $title !== '' ? $title : $car['make'].' '.$car['model'].' video',
                'alt' => $car['make'].' '.$car['model'].' video',
                'sort_order' => count($existing) + 1, 'created_at' => '',
            ]);
            activity('YouTube video added', $car['model'].' — '.$videoUrl);
            flash('success', 'YouTube video added to the vehicle gallery.');
        }

        if ($action === 'delete') {
            $id = (string)($_POST['media_id'] ?? '');
            $m = media_by_id($id);
            delete_media($id);
            activity('Media deleted', $m['path'] ?? $id);
            flash('success', 'Media deleted.');
        }

        if ($action === 'update') {
            $id = (string)($_POST['media_id'] ?? '');
            $m = media_by_id($id);
            if ($m) {
                $m['title'] = trim((string)($_POST['title'] ?? ''));
                $m['alt'] = trim((string)($_POST['alt'] ?? ''));
                $m['sort_order'] = (int)($_POST['sort_order'] ?? 99);
                $m['poster'] = trim((string)($_POST['poster'] ?? $m['poster'] ?? ''));
                save_media($m);
                flash('success', 'Media details updated.');
            }
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect(url('admin/media.php?car_id='.rawurlencode($carId)));
}

$adminPageTitle = 'Images & Videos';
$adminCurrent = 'media';
$items = $car ? media_for_car($carId) : [];
$imageItems = array_values(array_filter($items, fn($m) => ($m['type'] ?? '') === 'image'));
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<section class="admin-panel">
    <div class="admin-panel-header">
        <div>
            <h2>Vehicle Media Library</h2>
            <p class="admin-muted">Upload your own files or add YouTube stock-video links. The lowest display-order image becomes the main image.</p>
        </div>
        <form method="get">
            <select name="car_id" onchange="this.form.submit()" style="background:#080808;color:#fff;border:1px solid rgba(255,255,255,.15);padding:12px">
                <?php foreach($cars as $c): ?><option value="<?=h($c['id'])?>" <?=$c['id']===$carId?'selected':''?>><?=h($c['make'].' '.$c['model'])?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if(!$car): ?>
        <div class="empty-state">Add a vehicle before uploading media.</div>
    <?php else: ?>
        <div class="media-add-grid">
            <form class="upload-drop" method="post" enctype="multipart/form-data">
                <?=csrf_field()?>
                <input type="hidden" name="car_id" value="<?=h($carId)?>">
                <input type="hidden" name="action" value="upload">
                <p><strong>Upload Local Images or Videos</strong></p>
                <input type="file" name="media_files[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" required>
                <p class="admin-muted">JPG, PNG, WebP, MP4, WebM or MOV. Images up to 10 MB; videos up to 80 MB.</p>
                <button class="admin-button" type="submit">Upload Media</button>
            </form>

            <form class="youtube-add-card" method="post">
                <?=csrf_field()?>
                <input type="hidden" name="car_id" value="<?=h($carId)?>">
                <input type="hidden" name="action" value="add_youtube">
                <p><strong>Add YouTube Stock Video</strong></p>
                <label class="field"><span>YouTube URL</span><input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required></label>
                <label class="field"><span>Video Title</span><input type="text" name="video_title" placeholder="Vehicle walkaround or review"></label>
                <label class="field"><span>Gallery Poster</span>
                    <select name="poster">
                        <option value="">Use first vehicle image</option>
                        <?php foreach($imageItems as $image): ?><option value="<?=h($image['path'])?>"><?=h($image['title'] ?: basename($image['path']))?></option><?php endforeach; ?>
                    </select>
                </label>
                <p class="admin-muted">The video is embedded from YouTube and is not copied to your hosting storage.</p>
                <button class="admin-button" type="submit">Add YouTube Video</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php if($car): ?>
<section class="admin-panel">
    <div class="admin-panel-header"><h2><?=h($car['make'].' '.$car['model'])?></h2><span class="admin-muted"><?=count($items)?> media items</span></div>
    <?php if(!$items): ?>
        <div class="empty-state">No images or videos uploaded yet.</div>
    <?php else: ?>
        <div class="media-grid">
        <?php foreach($items as $item): ?>
            <article class="media-card">
                <div class="media-preview">
                    <?php if($item['type']==='video'): ?>
                        <video src="<?=asset($item['path'])?>" <?=!empty($item['poster'])?'poster="'.h(asset($item['poster'])).'"':''?> muted controls preload="metadata"></video>
                    <?php elseif($item['type']==='youtube'): ?>
                        <iframe src="<?=h(youtube_embed_url((string)$item['path']))?>" title="<?=h($item['title'])?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    <?php else: ?>
                        <img src="<?=asset($item['path'])?>" alt="<?=h($item['alt'])?>">
                    <?php endif; ?>
                    <span class="media-type"><?=h($item['type']==='youtube'?'YouTube':$item['type'])?></span>
                </div>
                <div class="media-card-body">
                    <form method="post">
                        <?=csrf_field()?>
                        <input type="hidden" name="car_id" value="<?=h($carId)?>">
                        <input type="hidden" name="media_id" value="<?=h($item['id'])?>">
                        <input type="hidden" name="action" value="update">
                        <label class="field"><span>Title</span><input type="text" name="title" value="<?=h($item['title'])?>"></label>
                        <label class="field"><span>Alt Text</span><input type="text" name="alt" value="<?=h($item['alt'])?>"></label>
                        <label class="field"><span>Display Order</span><input type="number" name="sort_order" value="<?=h($item['sort_order'])?>"></label>
                        <?php if(in_array($item['type'], ['video','youtube'], true)): ?>
                            <label class="field"><span>Video Poster</span>
                                <select name="poster"><option value="">No poster</option><?php foreach($imageItems as $image): ?><option value="<?=h($image['path'])?>" <?=($item['poster']??'')===$image['path']?'selected':''?>><?=h($image['title'] ?: basename($image['path']))?></option><?php endforeach; ?></select>
                            </label>
                        <?php endif; ?>
                        <p><?=h($item['path'])?></p>
                        <button class="admin-button secondary small" type="submit">Update</button>
                    </form>
                    <form method="post" style="margin-top:8px">
                        <?=csrf_field()?>
                        <input type="hidden" name="car_id" value="<?=h($carId)?>">
                        <input type="hidden" name="media_id" value="<?=h($item['id'])?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="admin-button danger small" type="submit" data-confirm="Remove this media item? Local uploads will be deleted permanently.">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
