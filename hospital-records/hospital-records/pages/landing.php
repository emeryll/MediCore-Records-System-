<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> · Hospital Records System (Zamboanga City)</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="landing-body">

<header class="landing-nav">
    <div class="landing-container landing-nav-inner">
        <nav class="landing-links" aria-label="Landing navigation">
            <a href="#about-wmmc">About Us</a>
            <a href="#registration-flow">How Registration Works</a>
            <a href="#portals">Hospital Portals</a>
        </nav>
        <div class="landing-nav-cta" style="display:flex; gap:10px; margin-left:auto;">
            <?= button('Activate patient account', ['variant' => 'secondary', 'href' => 'index.php?auth=activate']) ?>
            <?= button('Log in', ['icon' => 'lock', 'href' => 'index.php?auth=login']) ?>
        </div>
    </div>
</header>

<main>
    <!-- Official WMMC "Welcome to Wellness!" Full-Screen Hero Banner -->
    <section class="wmmc-hero-section">
        <div class="wmmc-hero-banner">
            <!-- Left Column: Official WMMC Logo + "Welcome to Wellness!" + Contact Info + Portal CTAs -->
            <div class="wmmc-hero-left">
                <img
                    src="<?= e(wmmc_logo_src()) ?>"
                    alt="West Metro Medical Center — Metro Pacific Health"
                    class="wmmc-hero-logo"
                >

                <h1 class="wmmc-welcome-script">Welcome to Wellness!</h1>

                <ul class="wmmc-contact-list" aria-label="Hospital contact details">
                    <li>
                        <span class="wmmc-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <span>0968 873 7637 &bull; 0998 978 9694</span>
                    </li>
                    <li>
                        <span class="wmmc-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <span>Veterans Avenue Extension, Tetuan, Zamboanga City</span>
                    </li>
                    <li>
                        <span class="wmmc-contact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                        </span>
                        <span>new.westmetro.com.ph</span>
                    </li>
                </ul>

                <div class="wmmc-hero-actions">
                    <?= button('Log in to your portal', ['icon' => 'arrow', 'href' => 'index.php?auth=login']) ?>
                    <?= button('Activate patient account', ['variant' => 'secondary', 'href' => 'index.php?auth=activate']) ?>
                </div>
            </div>

            <!-- Right Column: Official West Metro Medical Center Building Facade -->
            <div class="wmmc-hero-right">
                <img
                    src="<?= e(wmmc_building_src()) ?>"
                    alt="West Metro Medical Center Building Exterior — Veterans Avenue Extension, Tetuan, Zamboanga City"
                    class="wmmc-hero-building-img"
                >
            </div>
        </div>
    </section>

    <!-- About Us Section: West Metro Medical Center -->
    <section id="about-wmmc" class="landing-section">
        <div class="landing-container">
            <div class="wmmc-about-showcase">
                <div class="wmmc-about-copy">
                    <p class="eyebrow">About Us &middot; Metro Pacific Health Network</p>
                    <h2>Premier Tertiary Healthcare in Western Mindanao</h2>
                    <p>
                        Located at <strong><?= e(HOSPITAL_ADDRESS) ?></strong>, <strong>West Metro Medical Center</strong> is a proud member of the
                        <strong>Metro Pacific Health</strong> network and a trusted tertiary medical institution dedicated to compassionate,
                        patient-centered clinical care for families across Zamboanga City and the Zamboanga Peninsula.
                    </p>
                    <p>
                        Our integrated <strong>Hospital Records System</strong> connects Front Desk registration, outpatient consultations,
                        inpatient admissions, laboratory diagnostics, pharmacy prescriptions, and cashier billing under a single permanent
                        Medical Record Number (MRN).
                    </p>
                </div>
                <div class="wmmc-about-photo-card">
                    <img
                        src="<?= e(wmmc_building_src()) ?>"
                        alt="West Metro Medical Center Facade"
                        class="wmmc-about-img"
                    >
                    <div class="wmmc-about-caption">
                        <strong>West Metro Medical Center</strong>
                        <span>Veterans Avenue Extension, Tetuan, Zamboanga City &middot; 0968 873 7637</span>
                    </div>
                </div>
            </div>

            <div class="landing-features-grid" style="margin-top:34px; margin-bottom:0;">
                <div class="panel landing-feature-item">
                    <span class="stat-icon teal"><?= icon('patient', 20) ?></span>
                    <h3>Specialty Clinical Centers</h3>
                    <p>Home to our Heart &amp; Vascular Center, Hemodialysis Center, Maternal &amp; Child Wing, Surgical Pavilion, Bone &amp; Joint Orthopedics, and 24/7 Emergency Complex.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon navy"><?= icon('clipboard', 20) ?></span>
                    <h3>Single-Chart MRN Continuity</h3>
                    <p>Every fully registered patient receives a permanent Medical Record Number (MRN) that links outpatient appointments, lab results, prescriptions, admissions, and billing.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon green"><?= icon('shield', 20) ?></span>
                    <h3>Data Privacy Act Compliance</h3>
                    <p>Built in accordance with the Philippine Data Privacy Act of 2012 (RA 10173), featuring role-based access, OTP-verified contact updates, and a full clinical audit trail.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon amber"><?= icon('card', 20) ?></span>
                    <h3>PhilHealth &amp; HMO Accredited</h3>
                    <p>Seamless inpatient and outpatient billing with support for Cash, Card, PhilHealth case rates, and accredited HMO partners with official printable receipts.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration & MRN Approach -->
    <section id="registration-flow" class="landing-section landing-section-alt">
        <div class="landing-container">
            <div class="landing-section-head">
                <p class="eyebrow">Patient Registration &amp; Account Activation</p>
                <h2>How Patient Records &amp; Portal Activation Work</h2>
                <p>We verify patient identity in person before issuing a permanent Medical Record Number (MRN) and activating online portal access.</p>
            </div>

            <div class="landing-id-grid">
                <article class="panel landing-id-card">
                    <div class="landing-id-badge p-tone">1</div>
                    <span class="landing-id-example">Walk-In Patients</span>
                    <h3>Full Registration at Front Desk</h3>
                    <p>
                        When you visit West Metro Medical Center in person, Front Desk Staff verify your mobile number via SMS OTP, record your complete address, and issue your permanent <strong>MRN</strong>. You immediately receive an SMS invite to activate your online account.
                    </p>
                    <a class="text-link" href="index.php?auth=activate">Activate your account <?= icon('arrow', 14) ?></a>
                </article>

                <article class="panel landing-id-card">
                    <div class="landing-id-badge s-tone">2</div>
                    <span class="landing-id-example">Phone-In Patients</span>
                    <h3>Provisional Booking &amp; Check-In</h3>
                    <p>
                        Calling to book for the first time? Staff create a temporary <strong>Provisional record</strong> to hold your appointment slot and send an SMS confirmation. When you arrive for check-in, staff complete your full registration, assign your <strong>MRN</strong>, and send your account activation link.
                    </p>
                    <a class="text-link" href="index.php?auth=login">Staff &amp; Portal Login <?= icon('arrow', 14) ?></a>
                </article>

                <article class="panel landing-id-card">
                    <div class="landing-id-badge d-tone">3</div>
                    <span class="landing-id-example">Activated Patient Portal</span>
                    <h3>Self-Service Booking &amp; Records</h3>
                    <p>
                        Once registered, open the <strong>Activate Account</strong> link, enter your mobile number, verify the 6-digit OTP, and set your password. Log in anytime with your <strong>phone number or email and password</strong> to book doctors, view medical history, prescriptions, and bills.
                    </p>
                    <a class="text-link" href="index.php?auth=login">Log in to Patient Portal <?= icon('arrow', 14) ?></a>
                </article>
            </div>
        </div>
    </section>

    <!-- Role Portals Section -->
    <section id="portals" class="landing-section">
        <div class="landing-container">
            <div class="landing-section-head">
                <p class="eyebrow">Unified Login &middot; Role-Based Routing</p>
                <h2>One Login Page for All Hospital Portals</h2>
                <p>Sign in with your registered mobile number or email address and password. The system automatically routes you to your assigned portal.</p>
            </div>

            <div class="landing-features-grid" style="margin-bottom:32px;">
                <div class="panel landing-feature-item">
                    <span class="stat-icon teal"><?= icon('patient', 20) ?></span>
                    <h3>Patient Portal</h3>
                    <p>Book or reschedule appointments (up to 24h before), browse doctors by department, and view read-only medical history, prescriptions, and bills.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon navy"><?= icon('users', 20) ?></span>
                    <h3>Front Desk Staff Portal</h3>
                    <p>Search before creating records, perform Full or Quick Add (Provisional) registration, complete check-in upgrades, manage admissions, discharges, and live room beds.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon green"><?= icon('clipboard', 20) ?></span>
                    <h3>Doctor Portal</h3>
                    <p>Manage weekly schedules and leave dates, view daily appointments (including provisional flags), record consultations, orders, and mark inpatients For Discharge.</p>
                </div>
                <div class="panel landing-feature-item">
                    <span class="stat-icon amber"><?= icon('card', 20) ?></span>
                    <h3>Cashier &amp; Admin Portals</h3>
                    <p>Process automatic &amp; manual charges, Cash/Card/HMO/PhilHealth payments, discharge clearances, EOD reports, price lists, room setup, duplicate merges, and audit logs.</p>
                </div>
            </div>

            <div class="landing-cta-banner">
                <div>
                    <h2>West Metro Medical Center &middot; Hospital Records System</h2>
                    <p><?= e(HOSPITAL_ADDRESS) ?> &middot; <?= e(HOSPITAL_PHONE) ?> &middot; <?= e(HOSPITAL_WEBSITE) ?></p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <?= button('Activate patient account', ['variant' => 'secondary', 'href' => 'index.php?auth=activate']) ?>
                    <?= button('Log in now', ['icon' => 'lock', 'href' => 'index.php?auth=login']) ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="landing-container landing-footer-inner">
        <img src="<?= e(wmmc_logo_src()) ?>" alt="<?= e(APP_NAME) ?>" class="wmmc-footer-logo">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> (Metro Pacific Health) &middot; <?= e(HOSPITAL_ADDRESS) ?> &middot; All rights reserved.</p>
    </div>
</footer>

</body>
</html>
