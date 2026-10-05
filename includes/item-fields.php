<?php
/**
 * includes/item-fields.php
 * ---------------------------------------------------------------------
 * The fields shared by "sell an item" and "edit item", so the two forms
 * can never drift apart.
 *
 * Expects these to already be defined by the page including it:
 *   $old             values to re-display after a failed save
 *   $item            the existing row, when editing (null when adding)
 *   $allow_no_photo  true when editing, because the current photo can be kept
 */

// $config comes from config.php, which db.php already loaded. Reading it
// here rather than hard-coding "PHP" means changing config['currency']
// updates this label too.
global $config;

$value = static fn (string $key, string $fallback): string => (string) ($old[$key] ?? $fallback);

$current_title       = $value('title',       $item['title']       ?? '');
$current_description = $value('description', $item['description'] ?? '');
$current_price       = $value('price',       $item['price']       ?? '');
$current_stock       = $value('stock',       $item['stock']       ?? '1');
$current_photo       = $item['photo'] ?? '';
$photo_optional      = !empty($allow_no_photo);
?>

<div class="field">
    <label for="title">Title</label>
    <div class="control">
        <?= icon('tag', ['size' => 18, 'class' => 'control__icon']) ?>
        <input type="text" id="title" name="title" maxlength="120" required
               placeholder="Blue office chair, size M"
               value="<?= e($current_title) ?>">
    </div>
    <p class="help">Name the thing and the size. "Blue office chair" beats "Chair".</p>
</div>

<div class="field">
    <label for="description">Description</label>
    <div class="control control--area">
        <?= icon('info', ['size' => 18, 'class' => 'control__icon control__icon--top']) ?>
        <textarea id="description" name="description" rows="5"
                  placeholder="Condition, size, and why you are letting it go."
                  required><?= e($current_description) ?></textarea>
    </div>
    <p class="help">Condition, size, and why you are letting it go.</p>
</div>

<div class="field-pair">
    <div class="field">
        <label for="price">Price (<?= e((string) ($config['currency'] ?? 'PHP')) ?>)</label>
        <div class="control">
            <?= icon('tag', ['size' => 18, 'class' => 'control__icon']) ?>
            <input type="number" id="price" name="price" min="0" step="0.01" required
                   placeholder="0.00"
                   value="<?= e($current_price) ?>">
        </div>
    </div>

    <div class="field">
        <label for="stock">How many do you have?</label>
        <div class="control">
            <?= icon('package', ['size' => 18, 'class' => 'control__icon']) ?>
            <input type="number" id="stock" name="stock"
                   min="<?= $photo_optional ? '0' : '1' ?>" step="1" required
                   placeholder="1"
                   value="<?= e($current_stock) ?>">
        </div>
        <p class="help">
            <?= $photo_optional
                ? 'Set 0 to mark it sold out.'
                : 'Selling 50 chairs? Put 50 here, not 50 listings.' ?>
        </p>
    </div>
</div>

<div class="field">
    <label for="photo">Photo</label>
    <div class="control control--file">
        <?= icon('camera', ['size' => 18, 'class' => 'control__icon']) ?>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
               <?= $photo_optional ? '' : 'required' ?>>
    </div>
    <p class="help">
        JPG, PNG or WEBP, up to 3 MB.
        <?php if ($photo_optional && $current_photo !== ''): ?>
            <br>Leave this empty to keep the current photo.
        <?php endif; ?>
    </p>
    <?php if ($photo_optional && $current_photo !== '' && !empty($item['id'])): ?>
        <img class="field-photo" src="image.php?id=<?= (int) $item['id'] ?>"
             alt="Current photo" loading="lazy">
    <?php endif; ?>
</div>

<div class="form-actions">
    <button type="submit" class="btn">
        <?= icon($photo_optional ? 'check' : 'camera', ['size' => 18]) ?>
        <span><?= $photo_optional ? 'Save changes' : 'List this item' ?></span>
    </button>
    <a class="btn btn--quiet" href="<?= $photo_optional ? 'my-items.php' : 'index.php' ?>">Cancel</a>
</div>
