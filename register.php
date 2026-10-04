<?php
/**
 * register.php - create an account
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

if (is_logged_in()) {
    redirect('index.php');
}

$page_title = 'Create an account';
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

include __DIR__ . '/includes/header.php';
?>

<div class="auth">
    <div class="auth__pitch rise-1">
        <h1 class="auth__title">Create an account</h1>
        <p class="auth__lede">One account for buying and selling.</p>

        <ul class="auth__points">
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>Post an item with one photo</span></li>
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>Sell one, or sell fifty at once</span></li>
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>No payment details needed</span></li>
        </ul>

        <p class="auth__note">
            Your first name shows next to anything you list, so buyers know
            who they are dealing with. We never show your birthday or phone
            number publicly.
        </p>
    </div>

    <div class="panel panel--wide rise-2">

        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?= icon('circle-alert', ['size' => 16, 'class' => 'form-errors__icon']) ?>
                        <span><?= e($error) ?></span>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="process/register.php" method="post" novalidate>
            <?= csrf_field() ?>

            <!-- ---------- names ---------- -->
            <div class="field-pair">
                <div class="field">
                    <label for="first_name">First name</label>
                    <div class="control">
                        <?= icon('user', ['size' => 18, 'class' => 'control__icon']) ?>
                        <input type="text" id="first_name" name="first_name" maxlength="40"
                               required autocomplete="given-name"
                               value="<?= e($old['first_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="field">
                    <label for="last_name">Last name</label>
                    <div class="control">
                        <?= icon('user', ['size' => 18, 'class' => 'control__icon']) ?>
                        <input type="text" id="last_name" name="last_name" maxlength="40"
                               required autocomplete="family-name"
                               value="<?= e($old['last_name'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- ---------- email ---------- -->
            <div class="field">
                <label for="email">Email</label>
                <div class="control">
                    <?= icon('mail', ['size' => 18, 'class' => 'control__icon']) ?>
                    <input type="email" id="email" name="email" maxlength="190"
                           required autocomplete="email"
                           value="<?= e($old['email'] ?? '') ?>">
                </div>
            </div>

            <!-- ---------- birthday ---------- -->
            <div class="field">
                <label for="birth_date">Date of birth</label>
                <div class="control">
                    <?= icon('cake', ['size' => 18, 'class' => 'control__icon']) ?>
                    <?php
                    // The browser's own date picker, bounded so someone
                    // cannot pick a birthday in the future or 150 years ago.
                    $today    = date('Y-m-d');
                    $earliest = date('Y-m-d', strtotime('-120 years'));
                    ?>
                    <input type="date" id="birth_date" name="birth_date"
                           min="<?= $earliest ?>" max="<?= $today ?>" required
                           value="<?= e($old['birth_date'] ?? '') ?>">
                </div>
                <p class="help">
                    You must be 18 or over to trade. We use this to check your
                    age at signup, then keep it on file.
                </p>
            </div>

            <!-- ---------- password ---------- -->
            <div class="field">
                <label for="password">Password</label>
                <div class="control">
                    <?= icon('lock', ['size' => 18, 'class' => 'control__icon']) ?>
                    <input type="password" id="password" name="password" minlength="6"
                           required autocomplete="new-password"
                           data-strength-input>
                    <!-- Reveal toggle. The button works without JS because
                         it is only useful WITH JS; the field stays a plain
                         password input either way. -->
                    <button type="button" class="control__reveal"
                            data-reveal="password" aria-label="Show password"
                            aria-pressed="false">
                        <?= icon('eye', ['size' => 18, 'class' => 'reveal-on']) ?>
                        <?= icon('eye-off', ['size' => 18, 'class' => 'reveal-off']) ?>
                    </button>
                </div>
                <div class="strength" data-strength-meter hidden>
                    <span class="strength__bar"><span class="strength__fill"></span></span>
                    <span class="strength__label"></span>
                </div>
                <p class="help">At least six characters.</p>
            </div>

            <div class="field">
                <label for="password_confirm">Repeat password</label>
                <div class="control">
                    <?= icon('lock', ['size' => 18, 'class' => 'control__icon']) ?>
                    <input type="password" id="password_confirm" name="password_confirm"
                           minlength="6" required autocomplete="new-password"
                           data-match="password">
                </div>
                <p class="help" data-match-note></p>
            </div>

            <!-- ---------- optional ---------- -->
            <fieldset class="fieldset">
                <legend class="fieldset__legend">
                    <?= icon('info', ['size' => 15, 'class' => 'fieldset__icon']) ?>
                    Optional, but it helps buyers
                </legend>

                <div class="field-pair">
                    <div class="field">
                        <label for="phone">Phone</label>
                        <div class="control">
                            <?= icon('phone', ['size' => 18, 'class' => 'control__icon']) ?>
                            <input type="tel" id="phone" name="phone" maxlength="20"
                                   autocomplete="tel" placeholder="0917 123 4567"
                                   value="<?= e($old['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="city">City</label>
                        <div class="control">
                            <?= icon('map-pin', ['size' => 18, 'class' => 'control__icon']) ?>
                            <input type="text" id="city" name="city" maxlength="80"
                                   autocomplete="address-level2"
                                   placeholder="Quezon City"
                                   value="<?= e($old['city'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="gender">Gender</label>
                    <div class="control control--select">
                        <?= icon('user', ['size' => 18, 'class' => 'control__icon']) ?>
                        <select id="gender" name="gender">
<?php
                            // Re-select whatever they picked before, so a
                            // failed save does not silently reset it.
                            $current_gender = $old['gender'] ?? 'prefer_not_to_say';
                            $gender_options = [
                                'prefer_not_to_say' => 'Prefer not to say',
                                'female'            => 'Female',
                                'male'              => 'Male',
                                'other'             => 'Other',
                            ];
                            ?>
                            <?php foreach ($gender_options as $value => $label): ?>
                                <option value="<?= e($value) ?>"
                                    <?= $current_gender === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= icon('chevron-down', ['size' => 16, 'class' => 'control__caret']) ?>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--block">
                    <?= icon('user-plus', ['size' => 18]) ?>
                    <span>Create account</span>
                </button>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
