<?php
declare(strict_types=1);
$adminClaimed = db_admin_claimed();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> · Hospital Records & Patient Care Portal</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="landing-body">

<header class="landing-nav">
    <div class="landing-container landing-nav-inner">
        <a href="index.php" class="landing-brand-link"><?= logo() ?></a>
        <nav class="landing-links" aria-label="Landing navigation">
            <a href="#id-guide">How IDs Work</a>
            <a href="#portals">Role Portals</a>
            <a href="#features">Capabilities</a>
        </nav>
        <div class="landing-nav-cta" style="display:flex; gap:10px;">
            <?= button('Create account', ['variant' => 'secondary', 'href' => 'index.php?auth=register']) ?>
            <?= button('Sign in', ['icon' => 'lock', 'href' => 'index.php?auth=login']) ?>
        </div>
    </div>
</header>

<main>
    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="landing-container landing-hero-grid">
            <div class="landing-hero-copy">
                <span class="landing-pill"><?= icon('shield', 15) ?> HIPAA-Compliant Hospital Records System</span>
                <h1>Connected hospital care, from front desk to bedside.</h1>
                <p>
                    MediCore unifies patient registration, specialist appointment scheduling, inpatient room management,
                    clinical consultations, and patient self-service in one secure database-backed workspace.
                </p>
                <div class="landing-hero-actions">
                    <?= button('Sign in with your ID', ['icon' => 'arrow', 'href' => 'index.php?auth=login']) ?>
                    <?= button('Register your ID', ['variant' => 'secondary', 'href' => 'index.php?auth=register']) ?>
                </div>
                <div class="landing-trust-strip">
                    <span><b>P-xxxx</b> Patient ID (issued at consultation)</span>
                    <span><b>S-xxxx</b> Staff ID</span>
                    <span><b>D-xxxx</b> Doctor ID</span>
                </div>
            </div>

            <div class="landing-hero-card">
                <div class="landing-preview-top">
                    <?= logo(true) ?>
                    <?= badge('Database Active', 'success') ?>
                </div>
                <div class="landing-illu-wrap" aria-hidden="true">
                    <svg viewBox="0 0 560 320" fill="none">
                        <path class="illu-ground" d="M47 285c76-30 140-3 212-12 99-12 150-60 252-20"/>
                        <path class="illu-back" d="M129 63h292v210H129z"/>
                        <path class="illu-side" d="m129 63 62-33h292l-62 33H129Z"/>
                        <path class="illu-side" d="m421 63 62-33v210l-62 33V63Z"/>
                        <path class="illu-front" d="M208 114h135v159H208z"/>
                        <path class="illu-cross" d="M255 138h40v20h20v40h-20v20h-40v-20h-20v-40h20v-20Z"/>
                        <path class="illu-window" d="M150 92h32v30h-32zM150 144h32v30h-32zM150 196h32v30h-32zM368 92h32v30h-32zM368 144h32v30h-32zM368 196h32v30h-32z"/>
                        <path class="illu-door" d="M250 228h52v45h-52z"/>
                    </svg>
                </div>
                <div class="landing-quick-signin">
                    <p>Role-Based Prefix IDs</p>
                    <div class="landing-id-buttons">
                        <a href="index.php?auth=login" class="landing-id-chip">
                            <strong>P-xxxx</strong>
                            <span>Patient Portal</span>
                        </a>
                        <a href="index.php?auth=login" class="landing-id-chip">
                            <strong>S-xxxx</strong>
                            <span>Front Desk Staff</span>
                        </a>
                        <a href="index.php?auth=login" class="landing-id-chip">
                            <strong>D-xxxx</strong>
                            <span>Doctor Workspace</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How IDs Work Section -->
    <section id="id-guide" class="landing-section">
        <div class="landing-container">
            <div class="landing-section-head">
                <p class="eyebrow">Role-Based ID Authentication</p>
                <h2>Every user signs in with their assigned MediCore ID</h2>
                <p>Your ID prefix automatically routes you to the right clinical, administrative, or patient portal.</p>
            </div>

            <div class="landing-id-grid">
                <article class="panel landing-id-card">
                    <div class="landing-id-badge p-tone">P</div>
                    <span class="landing-id-example">Format: <code>P-1001</code></span>
                    <h3>Patients (Issued by Staff)</h3>
                    <p>
                        When you visit the hospital or register at the front desk, staff issue your personal
                        <strong>P-xxxx</strong> Patient ID. Use it to activate your account, book appointments online,
                        view your medical history, check prescriptions, and download billing statements.
                    </p>
                    <a class="text-link" href="index.php?auth=login">Sign in with P-ID <?= icon('arrow', 14) ?></a>
                </article>

                <article class="panel landing-id-card">
                    <div class="landing-id-badge s-tone">S</div>
                    <span class="landing-id-example">Format: <code>S-1001</code></span>
                    <h3>Front Desk & Staff</h3>
                    <p>
                        Front desk personnel sign in with their <strong>S-xxxx</strong> Staff ID to register new patients
                        (generating their <code>P-</code> ID), schedule appointments, manage room occupancy, process admissions
                        and discharges, and verify payment receipts.
                    </p>
                    <a class="text-link" href="index.php?auth=login">Sign in with S-ID <?= icon('arrow', 14) ?></a>
                </article>

                <article class="panel landing-id-card">
                    <div class="landing-id-badge d-tone">D</div>
                    <span class="landing-id-example">Format: <code>D-1001</code></span>
                    <h3>Doctors & Specialists</h3>
                    <p>
                        Attending physicians sign in with their <strong>D-xxxx</strong> Doctor ID to review their daily
                        appointment timeline, start patient consultations, document ICD-10 diagnoses, prescribe medications,
                        and review laboratory results.
                    </p>
                    <a class="text-link" href="index.php?auth=login">Sign in with D-ID <?= icon('arrow', 14) ?></a>
                </article>
            </div>
        </div>
    </section>

    <!-- Portals & Capabilities Section -->
    <section id="portals" class="landing-section landing-section-alt">
        <div class="landing-container" id="features">
            <div class="landing-section-head">
                <p class="eyebrow">Unified Hospital Workflow</p>
                <h2>Built for every step of the patient journey</h2>
                <p>Everything is persisted in the hospital database across Patient, Front Desk, Doctor, and Administrator views.</p>
            </div>

            <div class="landing-features-grid">
                <div class="panel landing-feature-item">
                    <span class="stat-icon teal"><?= icon('calendar', 20) ?></span>
                    <h3>3-Step Online Booking</h3>
                    <p>Choose a department and specialist, pick an open date and time chip on the live calendar, and confirm with conflict protection.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon navy"><?= icon('bed', 20) ?></span>
                    <h3>Live Room & Admission Sync</h3>
                    <p>Admitting a patient automatically marks their room Occupied; discharging with proof immediately frees the room for the next admission.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon green"><?= icon('clipboard', 20) ?></span>
                    <h3>Structured Clinical Records</h3>
                    <p>Doctors record chief complaints, clinical notes, ICD-10 diagnoses, medication prescriptions, and lab orders in one consultation flow.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon amber"><?= icon('shield', 20) ?></span>
                    <h3>Single-Admin Security</h3>
                    <p>Only one Administrator (<code>A</code> ID) account can ever be registered in the database. Once claimed, the <code>A</code> prefix is permanently locked.</p>
                </div>
            </div>

            <div class="landing-cta-banner">
                <div>
                    <h2>Ready to access your MediCore workspace?</h2>
                    <p>Sign in with your registered ID or create your account now.</p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <?= button('Create account', ['variant' => 'secondary', 'href' => 'index.php?auth=register']) ?>
                    <?= button('Sign in now', ['icon' => 'lock', 'href' => 'index.php?auth=login']) ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-container landing-footer-inner">
        <?= logo() ?>
        <p>© <?= date('Y') ?> <?= e(APP_NAME) ?> Health · Hospital Records System · <a class="text-link" href="index.php?auth=login">Sign in</a> · <a class="text-link" href="index.php?auth=register">Create account</a></p>
    </div>
</footer>

</body>
</html>
