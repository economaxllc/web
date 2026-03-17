<?php
declare(strict_types=1);

// ── Quote Form Handler ─────────────────────────────────────────────────────
$formSuccess = false;
$formError   = false;
$formMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quote_submit'])) {

    // Sanitise & validate inputs
    $firstName  = htmlspecialchars(trim($_POST['first_name']    ?? ''), ENT_QUOTES, 'UTF-8');
    $lastName   = htmlspecialchars(trim($_POST['last_name']     ?? ''), ENT_QUOTES, 'UTF-8');
    $phone      = htmlspecialchars(trim($_POST['phone']         ?? ''), ENT_QUOTES, 'UTF-8');
    $email      = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $company    = htmlspecialchars(trim($_POST['company']       ?? ''), ENT_QUOTES, 'UTF-8');
    $material   = htmlspecialchars(trim($_POST['material']      ?? ''), ENT_QUOTES, 'UTF-8');
    $loads      = htmlspecialchars(trim($_POST['loads']         ?? ''), ENT_QUOTES, 'UTF-8');
    $desired    = htmlspecialchars(trim($_POST['desired_date']  ?? ''), ENT_QUOTES, 'UTF-8');
    $notes      = htmlspecialchars(trim($_POST['notes']         ?? ''), ENT_QUOTES, 'UTF-8');

    // Basic required-field check
    if ($firstName === '' || $lastName === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError   = true;
        $formMessage = 'Please fill in all required fields with a valid email address.';
    } else {
        $to      = 'quotes@economaxtrucking.com';
        $subject = "Quote Request – {$firstName} {$lastName}" . ($company !== '' ? " ({$company})" : '');

        $body = <<<TEXT
        New quote request from the EconoMax website.
        ───────────────────────────────────────────
        Name    : {$firstName} {$lastName}
        Phone   : {$phone}
        Email   : {$email}
        Company : {$company}
        ───────────────────────────────────────────
        Material       : {$material}
        Estimated Loads: {$loads}
        Desired Date   : {$desired}

        Project Notes / Details:
        {$notes}
        ───────────────────────────────────────────
        Sent from EconoMaxTrucking.com quote form
        TEXT;

        // Build headers array – PHP 8.3 supports array headers directly with mail()
        $headers = implode("\r\n", [
            'From: EconoMaxTrucking.com Website <noreply@economaxtrucking.com>',
            "Reply-To: {$firstName} {$lastName} <{$email}>",
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
            'X-Form-Source: EconoMax Quote Form',
        ]);

        $formSuccess = mail($to, $subject, $body, $headers);
        $formError   = !$formSuccess;
        $formMessage = $formSuccess
            ? 'Your quote request has been sent! We\'ll be in touch soon.'
            : 'Sorry, there was a problem sending your message. Please call us directly.';
    }
}

// ── Fleet Image Scanner ───────────────────────────────────────────────────
$fleetDir    = __DIR__ . '/images/fleet/';
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$fleetImages = [];

if (is_dir($fleetDir)) {
    $iterator = new FilesystemIterator($fleetDir, FilesystemIterator::SKIP_DOTS);
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowedExts, strict: true)) {
                $fleetImages[] = $file->getFilename();
            }
        }
    }
    natsort($fleetImages);            // natural sort: fleet2 before fleet10
    $fleetImages = array_values($fleetImages);
}

// JSON-encode the image list for inline JS (safe for embedding)
$fleetImagesJson = json_encode(
    array_map(fn(string $f): string => '/images/fleet/' . rawurlencode($f), $fleetImages),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EconoMax.LLC</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@400;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet" />
  <style>
    :root {
      --yellow: #F5C200;
      --black: #0D0D0D;
      --steel: #1A1A1A;
      --mid: #2C2C2C;
      --gray: #555;
      --light: #E8E2D9;
      --white: #FAFAF8;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Barlow', sans-serif;
      background: var(--black);
      color: var(--white);
      overflow-x: hidden;
    }

    /* ── NAV ── */
    nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 40px;
      height: 64px;
      background: rgba(13,13,13,0.92);
      backdrop-filter: blur(8px);
      border-bottom: 2px solid var(--yellow);
    }

    .logo {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      letter-spacing: 0.06em;
      color: var(--white);
    }
    .logo span { color: var(--yellow); }

    .nav-links {
      display: flex;
      gap: 36px;
      list-style: none;
    }
    .nav-links a {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 600;
      font-size: 0.95rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--light);
      text-decoration: none;
      transition: color 0.2s;
    }
    .nav-links a:hover { color: var(--yellow); }

    .nav-cta {
      background: var(--yellow);
      color: var(--black);
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 0.9rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 10px 24px;
      border: none;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }
    .nav-cta:hover { background: #ffd835; transform: translateY(-1px); }

    /* ── HERO ── */
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: flex-end;
      padding: 0 60px 80px;
      overflow: hidden;
    }

    /* Diagonal stripe background */
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        repeating-linear-gradient(
          -55deg,
          transparent,
          transparent 60px,
          rgba(245,194,0,0.04) 60px,
          rgba(245,194,0,0.04) 62px
        );
    }

    /* Big number watermark */
    .hero-bg-text {
      position: absolute;
      bottom: -60px;
      right: -40px;
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(200px, 30vw, 480px);
      color: rgba(245,194,0,0.055);
      line-height: 1;
      pointer-events: none;
      user-select: none;
    }

    /* Truck SVG illustration area */
    .hero-truck {
      position: absolute;
      right: 0;
      bottom: 0;
      width: 58%;
      max-width: 900px;
      opacity: 0;
      transform: translateX(60px);
      animation: slidein 1s 0.3s cubic-bezier(0.16,1,0.3,1) forwards;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 560px;
      opacity: 0;
      transform: translateY(30px);
      animation: fadein 0.9s 0.1s ease forwards;
    }

    .hero-eyebrow {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 0.85rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--yellow);
      margin-bottom: 20px;
    }

    .hero-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(64px, 9vw, 120px);
      line-height: 0.95;
      letter-spacing: 0.02em;
      color: var(--white);
      margin-bottom: 28px;
    }
    .hero-title em {
      font-style: normal;
      color: var(--yellow);
      display: block;
    }

    .hero-sub {
      font-size: 1.05rem;
      line-height: 1.65;
      color: #AAA;
      max-width: 420px;
      margin-bottom: 44px;
    }

    .hero-actions {
      display: flex;
      gap: 16px;
      align-items: center;
      flex-wrap: wrap;
    }

    .btn-primary {
      background: var(--yellow);
      color: var(--black);
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 16px 36px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background 0.2s, transform 0.15s;
      clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 100%, 10px 100%);
    }
    .btn-primary:hover { background: #ffd835; transform: translateY(-2px); }

    .btn-outline {
      background: transparent;
      color: var(--white);
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 600;
      font-size: 1rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 15px 34px;
      border: 2px solid #555;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: border-color 0.2s, color 0.2s;
    }
    .btn-outline:hover { border-color: var(--yellow); color: var(--yellow); }

    /* Truck SVG inline */
    .truck-svg {
      width: 100%;
      height: auto;
      display: block;
    }

    /* ── STATS STRIP ── */
    .stats-strip {
      background: var(--yellow);
      padding: 28px 60px;
      display: flex;
      justify-content: space-around;
      flex-wrap: wrap;
      gap: 20px;
    }

    .stat {
      text-align: center;
    }
    .stat-num {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 3rem;
      color: var(--black);
      line-height: 1;
    }
    .stat-label {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 600;
      font-size: 0.8rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #3a3000;
    }

    /* ── SERVICES ── */
    .section {
      padding: 100px 60px;
    }

    .section-label {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 0.8rem;
      letter-spacing: 0.24em;
      text-transform: uppercase;
      color: var(--yellow);
      margin-bottom: 14px;
    }

    .section-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(40px, 6vw, 76px);
      line-height: 1;
      letter-spacing: 0.02em;
      margin-bottom: 60px;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2px;
    }

    .service-card {
      background: var(--steel);
      padding: 44px 36px;
      border-top: 3px solid transparent;
      transition: border-color 0.25s, background 0.25s;
      cursor: default;
    }
    .service-card:hover {
      border-top-color: var(--yellow);
      background: var(--mid);
    }

    .service-icon {
      font-size: 2.4rem;
      margin-bottom: 20px;
      display: block;
    }

    .service-name {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 1.35rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--white);
      margin-bottom: 12px;
    }

    .service-desc {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #888;
    }

    /* ── WHY US ── */
    .why-section {
      padding: 100px 60px;
      background: var(--steel);
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .why-text {}

    .why-list {
      list-style: none;
      margin-top: 40px;
    }
    .why-list li {
      display: flex;
      align-items: flex-start;
      gap: 18px;
      padding: 24px 0;
      border-bottom: 1px solid #333;
    }
    .why-list li:last-child { border-bottom: none; }

    .why-num {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      color: var(--yellow);
      line-height: 1;
      flex-shrink: 0;
      width: 40px;
    }

    .why-info h4 {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .why-info p { font-size: 0.9rem; color: #888; line-height: 1.5; }

    /* Big diagonal callout */
    .callout-box {
      background: var(--yellow);
      color: var(--black);
      padding: 60px 50px;
      position: relative;
      overflow: hidden;
    }
    .callout-box::after {
      content: '✓';
      position: absolute;
      right: -30px;
      bottom: -40px;
      font-size: 240px;
      opacity: 0.08;
      font-family: 'Bebas Neue', sans-serif;
      line-height: 1;
    }
    .callout-box h3 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 3.2rem;
      line-height: 1;
      margin-bottom: 18px;
    }
    .callout-box p {
      font-family: 'Barlow', sans-serif;
      font-size: 1rem;
      line-height: 1.6;
      color: #3a3000;
      max-width: 340px;
      margin-bottom: 32px;
    }
    .callout-box .btn-dark {
      background: var(--black);
      color: var(--yellow);
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 14px 32px;
      border: none;
      cursor: pointer;
      display: inline-block;
      text-decoration: none;
      transition: opacity 0.2s;
      clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 100%, 8px 100%);
    }
    .callout-box .btn-dark:hover { opacity: 0.85; }

    /* ── CONTACT ── */
    .contact-section {
      padding: 100px 60px;
      background: var(--black);
    }

    .contact-grid {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 80px;
      align-items: start;
    }

    .contact-info h2 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(40px, 5vw, 68px);
      line-height: 1;
      margin-bottom: 24px;
    }
    .contact-info p {
      color: #888;
      line-height: 1.65;
      margin-bottom: 40px;
    }

    .contact-detail {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }
    .contact-detail-icon {
      width: 44px;
      height: 44px;
      background: var(--yellow);
      color: var(--black);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .contact-detail-text strong {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: #666;
      display: block;
    }
    .contact-detail-text span {
      font-size: 1rem;
      color: var(--white);
    }

    /* Form */
    .contact-form {
      background: var(--steel);
      padding: 50px 44px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-group {
      margin-bottom: 18px;
    }
    .form-group label {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 600;
      font-size: 0.78rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #777;
      display: block;
      margin-bottom: 8px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      background: var(--mid);
      border: 1px solid #3a3a3a;
      color: var(--white);
      font-family: 'Barlow', sans-serif;
      font-size: 0.95rem;
      padding: 12px 16px;
      outline: none;
      transition: border-color 0.2s;
      -webkit-appearance: none;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: var(--yellow);
    }
    .form-group textarea { resize: vertical; min-height: 100px; }
    .form-group select option { background: var(--mid); }

    .form-submit {
      width: 100%;
      background: var(--yellow);
      color: var(--black);
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.4rem;
      letter-spacing: 0.1em;
      padding: 16px;
      border: none;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s, transform 0.15s;
      clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 100%, 12px 100%);
    }
    .form-submit:hover { background: #ffd835; transform: translateY(-2px); }

    /* ── FOOTER ── */
    footer {
      background: #070707;
      border-top: 2px solid #1a1a1a;
      padding: 40px 60px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .footer-logo {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.6rem;
      letter-spacing: 0.06em;
    }
    .footer-logo span { color: var(--yellow); }

    .footer-links {
      display: flex;
      gap: 28px;
      list-style: none;
    }
    .footer-links a {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.85rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #555;
      text-decoration: none;
      transition: color 0.2s;
    }
    .footer-links a:hover { color: var(--yellow); }

    .footer-copy {
      font-size: 0.8rem;
      color: #444;
    }

    /* ── Ticket strip divider ── */
    .divider-stripe {
      height: 12px;
      background: repeating-linear-gradient(
        90deg,
        var(--yellow) 0px,
        var(--yellow) 24px,
        var(--black) 24px,
        var(--black) 36px
      );
    }

    /* ── Animations ── */
    @keyframes fadein {
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slidein {
      to { opacity: 1; transform: translateX(0); }
    }

    /* ── FLEET INVENTORY ── */
    .fleet-section {
      padding: 100px 60px;
      background: var(--black);
    }

    .fleet-inner {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: start;
    }

    /* Fleet Table */
    .fleet-table-wrap {
      overflow-x: auto;
    }

    .fleet-table {
      width: 100%;
      border-collapse: collapse;
      font-family: 'Barlow Condensed', sans-serif;
    }

    .fleet-table thead tr {
      background: var(--yellow);
    }

    .fleet-table thead th {
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 0.78rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--black);
      padding: 14px 20px;
      text-align: left;
      white-space: nowrap;
    }

    .fleet-table tbody tr {
      border-bottom: 1px solid #222;
      transition: background 0.2s;
    }

    .fleet-table tbody tr:hover {
      background: var(--steel);
    }

    .fleet-table tbody td {
      padding: 16px 20px;
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      color: var(--light);
    }

    .fleet-table tbody td:first-child {
      color: var(--white);
      font-size: 1.05rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 700;
      font-size: 0.75rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #1a3a1a;
      background: #b6f5b6;
      padding: 4px 12px;
    }

    .status-badge::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #1a7a1a;
      display: inline-block;
      flex-shrink: 0;
    }

    /* Fleet Carousel */
    .fleet-carousel {
      position: sticky;
      top: 84px;
    }

    .carousel-viewport {
      position: relative;
      width: 100%;
      aspect-ratio: 4/3;
      background: var(--steel);
      overflow: hidden;
    }

    .carousel-slide {
      position: absolute;
      inset: 0;
      opacity: 0;
      transition: opacity 0.5s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .carousel-slide.active {
      opacity: 1;
    }

    .carousel-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .carousel-slide .no-img {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      color: #444;
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.85rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      text-align: center;
      padding: 20px;
      width: 100%;
      height: 100%;
    }

    .carousel-slide .no-img svg {
      opacity: 0.25;
    }

    .carousel-counter {
      position: absolute;
      top: 14px;
      right: 14px;
      background: rgba(0,0,0,0.7);
      font-family: 'Barlow Condensed', sans-serif;
      font-weight: 600;
      font-size: 0.8rem;
      letter-spacing: 0.12em;
      color: var(--light);
      padding: 5px 12px;
      z-index: 2;
    }

    .carousel-controls {
      display: flex;
      gap: 2px;
      margin-top: 2px;
    }

    .carousel-btn {
      flex: 1;
      background: var(--steel);
      border: none;
      color: var(--white);
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.5rem;
      padding: 14px;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
      letter-spacing: 0.05em;
    }

    .carousel-btn:hover {
      background: var(--yellow);
      color: var(--black);
    }

    .carousel-dots {
      display: flex;
      gap: 6px;
      justify-content: center;
      padding: 14px 0 0;
    }

    .carousel-dot {
      width: 8px;
      height: 8px;
      background: #333;
      border: none;
      cursor: pointer;
      padding: 0;
      transition: background 0.2s, transform 0.2s;
    }

    .carousel-dot.active {
      background: var(--yellow);
      transform: scaleX(2.5);
    }

    .carousel-label {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #555;
      text-align: center;
      padding-top: 10px;
    }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .hero { padding: 100px 32px 60px; align-items: center; }
      .hero-truck { display: none; }
      .hero-content { max-width: 100%; }
      .stats-strip { padding: 24px 32px; }
      .section, .contact-section { padding: 70px 32px; }
      .why-section { grid-template-columns: 1fr; padding: 70px 32px; gap: 40px; }
      .contact-grid { grid-template-columns: 1fr; }
      .form-row { grid-template-columns: 1fr; }
      footer { flex-direction: column; align-items: flex-start; padding: 32px; }
      nav { padding: 0 24px; }
      .nav-links { display: none; }
      .fleet-section { padding: 70px 32px; }
      .fleet-inner { grid-template-columns: 1fr; gap: 40px; }
      .fleet-carousel { position: static; }
    }
  </style>
</head>
<body>

  <!-- NAV -->
  <nav>
    <div class="logo">Econo<span>Max</span></div>
    <ul class="nav-links">
      <li><a href="#services">Services</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#fleet">Fleet</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <button class="nav-cta" onclick="document.getElementById('contact').scrollIntoView({behavior:'smooth'})">Get a Quote</button>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg-text">HAUL</div>

    <!-- Inline truck SVG -->
    <div class="hero-truck">
      <svg class="truck-svg" viewBox="0 0 900 480" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Ground shadow -->
        <ellipse cx="450" cy="460" rx="380" ry="18" fill="rgba(0,0,0,0.6)"/>
        <!-- Truck body/chassis -->
        <rect x="60" y="270" width="720" height="40" rx="4" fill="#1c1c1c"/>
        <!-- Dump bed -->
        <rect x="200" y="140" width="480" height="140" rx="6" fill="#2a2a2a"/>
        <rect x="200" y="140" width="480" height="12" rx="3" fill="#F5C200"/>
        <!-- Bed ribs -->
        <rect x="260" y="152" width="6" height="128" rx="2" fill="#1a1a1a" opacity="0.7"/>
        <rect x="340" y="152" width="6" height="128" rx="2" fill="#1a1a1a" opacity="0.7"/>
        <rect x="420" y="152" width="6" height="128" rx="2" fill="#1a1a1a" opacity="0.7"/>
        <rect x="500" y="152" width="6" height="128" rx="2" fill="#1a1a1a" opacity="0.7"/>
        <rect x="580" y="152" width="6" height="128" rx="2" fill="#1a1a1a" opacity="0.7"/>
        <!-- Cab -->
        <rect x="60" y="200" width="190" height="110" rx="8" fill="#222"/>
        <!-- Cab roof -->
        <path d="M80 200 Q100 160 200 158 L250 200Z" fill="#1a1a1a"/>
        <!-- Windshield -->
        <path d="M86 196 Q104 164 196 162 L244 196Z" fill="#1E3A5F" opacity="0.9"/>
        <!-- Cab stripe -->
        <rect x="60" y="270" width="190" height="8" fill="#F5C200"/>
        <!-- Door line -->
        <line x1="160" y1="205" x2="160" y2="270" stroke="#111" stroke-width="2"/>
        <!-- Door handle -->
        <rect x="168" y="238" width="18" height="5" rx="2" fill="#F5C200"/>
        <!-- Exhaust -->
        <rect x="240" y="145" width="12" height="70" rx="4" fill="#333"/>
        <!-- Exhaust smoke -->
        <circle cx="246" cy="130" r="10" fill="#333" opacity="0.35"/>
        <circle cx="250" cy="113" r="8" fill="#333" opacity="0.22"/>
        <circle cx="244" cy="98" r="6" fill="#333" opacity="0.12"/>
        <!-- Front bumper -->
        <rect x="44" y="275" width="24" height="32" rx="3" fill="#333"/>
        <!-- Headlights -->
        <rect x="64" y="245" width="16" height="10" rx="2" fill="#F5C200" opacity="0.9"/>
        <!-- Wheels -->
        <!-- Front wheel -->
        <circle cx="130" cy="340" r="54" fill="#1a1a1a"/>
        <circle cx="130" cy="340" r="36" fill="#111"/>
        <circle cx="130" cy="340" r="20" fill="#2a2a2a"/>
        <circle cx="130" cy="340" r="5" fill="#F5C200"/>
        <!-- Rear wheel 1 -->
        <circle cx="540" cy="340" r="54" fill="#1a1a1a"/>
        <circle cx="540" cy="340" r="36" fill="#111"/>
        <circle cx="540" cy="340" r="20" fill="#2a2a2a"/>
        <circle cx="540" cy="340" r="5" fill="#F5C200"/>
        <!-- Rear wheel 2 -->
        <circle cx="660" cy="340" r="54" fill="#1a1a1a"/>
        <circle cx="660" cy="340" r="36" fill="#111"/>
        <circle cx="660" cy="340" r="20" fill="#2a2a2a"/>
        <circle cx="660" cy="340" r="5" fill="#F5C200"/>
        <!-- Tread lines front -->
        <path d="M84 310 A54 54 0 0 1 176 310" stroke="#2a2a2a" stroke-width="5" fill="none"/>
        <path d="M80 350 A54 54 0 0 0 180 350" stroke="#2a2a2a" stroke-width="5" fill="none"/>
        <!-- Company text on bed -->
        <text x="450" y="225" font-family="'Bebas Neue',sans-serif" font-size="38" fill="#F5C200" text-anchor="middle" letter-spacing="4" opacity="0.85">ECONOMAX, LLC</text>
        <!-- Phone number -->
        <text x="450" y="260" font-family="'Barlow Condensed',sans-serif" font-size="18" fill="rgba(255,255,255,0.5)" text-anchor="middle" letter-spacing="2">(678) 793-8807 or (678) 793-0711</text>
      </svg>
    </div>

    <div class="hero-content">
      <p class="hero-eyebrow">Licensed &amp; Insured · Est. 2006</p>
      <h1 class="hero-title">We<br>Move<br><em>Mountains.</em></h1>
      <p class="hero-sub">Heavy hauling, site clearing, and material delivery across the region. Fleet of 5 trucks. No load too large.</p>
      <div class="hero-actions">
        <a href="#contact" class="btn-primary">Request a Quote</a>
        <a href="#services" class="btn-outline">Our Services</a>
      </div>
    </div>
  </section>

  <!-- STATS STRIP -->
  <div class="stats-strip">
    <div class="stat">
      <div class="stat-num">5+</div>
      <div class="stat-label">Trucks in Fleet</div>
    </div>
    <div class="stat">
      <div class="stat-num">3.5K+</div>
      <div class="stat-label">Loads Delivered</div>
    </div>
    <div class="stat">
      <div class="stat-num">20</div>
      <div class="stat-label">Years in Business</div>
    </div>
    <div class="stat">
      <div class="stat-num">98%</div>
      <div class="stat-label">On-Time Rate</div>
    </div>
    <div class="stat">
      <div class="stat-num">24/7</div>
      <div class="stat-label">Dispatch Available</div>
    </div>
  </div>

  <!-- SERVICES -->
  <section class="section" id="services">
    <div class="section-label">What We Haul</div>
    <h2 class="section-title">Our Services</h2>
    <div class="services-grid">
      <div class="service-card">
        <span class="service-icon">🪨</span>
        <div class="service-name">Aggregate &amp; Gravel</div>
        <p class="service-desc">Crushed stone, pea gravel, road base, and fill material delivered to any job site — residential or commercial.</p>
      </div>
      <div class="service-card">
        <span class="service-icon">🏗️</span>
        <div class="service-name">Construction Debris</div>
        <p class="service-desc">Fast removal of concrete, demolition debris, and excavated material. We keep your site clean and your timeline on track.</p>
      </div>
      <div class="service-card">
        <span class="service-icon">🌱</span>
        <div class="service-name">Topsoil &amp; Mulch</div>
        <p class="service-desc">Premium screened topsoil, compost, and mulch by the truckload for landscaping and site preparation projects.</p>
      </div>
      <div class="service-card">
        <span class="service-icon">⚫</span>
        <div class="service-name">Asphalt &amp; Sand</div>
        <p class="service-desc">Hot mix asphalt, millings, and construction sand delivered directly to your paving and road-work sites.</p>
      </div>
      <div class="service-card">
        <span class="service-icon">🔥</span>
        <div class="service-name">Coal &amp; Fuel Material</div>
        <p class="service-desc">Bulk coal, coke, and fuel aggregate transport for industrial plants and energy facilities.</p>
      </div>
      <div class="service-card">
        <span class="service-icon">📦</span>
        <div class="service-name">Custom Hauling</div>
        <p class="service-desc">Don't see what you need? We haul it. Call us to discuss any oversized, specialty, or out-of-area load requirement.</p>
      </div>
    </div>
  </section>

  <div class="divider-stripe"></div>

  <!-- WHY US -->
  <section class="why-section" id="about">
    <div class="why-text">
      <div class="section-label">Why Choose EconoMax, LLC</div>
      <h2 class="section-title">Built on<br>Reliability</h2>
      <ul class="why-list">
        <li>
          <span class="why-num">01</span>
          <div class="why-info">
            <h4>Modern Fleet, Maintained Daily</h4>
            <p>Every truck undergoes pre-trip inspections. Zero breakdowns means zero delays for you.</p>
          </div>
        </li>
        <li>
          <span class="why-num">02</span>
          <div class="why-info">
            <h4>Experienced, CDL-Licensed Drivers</h4>
            <p>All drivers carry full CDL credentials with hazmat endorsements where required.</p>
          </div>
        </li>
        <li>
          <span class="why-num">03</span>
          <div class="why-info">
            <h4>Competitive, Transparent Pricing</h4>
            <p>Flat-rate and per-ton options available. No hidden fees, no surprise invoices.</p>
          </div>
        </li>
        <li>
          <span class="why-num">04</span>
          <div class="why-info">
            <h4>24/7 Dispatch &amp; Scheduling</h4>
            <p>Early morning pour? Weekend deadline? We work around your schedule, not ours.</p>
          </div>
        </li>
      </ul>
    </div>

    <div class="callout-box">
      <h3>Ready to Move<br>Material Today?</h3>
      <p>Same-day and next-morning dispatch available for most load types in our service area. Call now and talk to a real dispatcher.</p>
      <a href="tel:6787938807" class="btn-dark">📞 Call (678) 793-8807</a> or <a href="tel:6787930711" class="btn-dark">📞 Call (678) 793-0711</a>
    </div>
  </section>

  <div class="divider-stripe"></div>

  <!-- FLEET INVENTORY -->
  <section class="fleet-section" id="fleet">
    <div class="section-label">Our Equipment</div>
    <h2 class="section-title">Fleet Inventory</h2>

    <div class="fleet-inner">

      <!-- Table -->
      <div class="fleet-table-wrap">
        <table class="fleet-table">
          <thead>
            <tr>
              <th>Year / Make / Model</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>2019 Volvo VHD64F200</td>
              <td><span class="status-badge">Active</span></td>
            </tr>
            <tr>
              <td>2019 Kenworth T880</td>
              <td><span class="status-badge">Active</span></td>
            </tr>
            <tr>
              <td>2019 Kenworth W900B</td>
              <td><span class="status-badge">Active</span></td>
            </tr>
            <tr>
              <td>2016 Kenworth T880</td>
              <td><span class="status-badge">Active</span></td>
            </tr>
            <tr>
              <td>2018 Volvo VHD64B300</td>
              <td><span class="status-badge">Active</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Carousel -->
      <div class="fleet-carousel">
        <div class="carousel-viewport" id="carouselViewport">
          <div class="carousel-counter"><span id="carouselCurrent">1</span> / <span id="carouselTotal">—</span></div>
          <!-- Slides injected by JS -->
        </div>
        <div class="carousel-controls">
          <button class="carousel-btn" id="prevBtn" onclick="carouselMove(-1)">&#8592; Prev</button>
          <button class="carousel-btn" id="nextBtn" onclick="carouselMove(1)">Next &#8594;</button>
        </div>
        <div class="carousel-dots" id="carouselDots"></div>
        <p class="carousel-label" id="carouselLabel">Fleet Photos</p>
      </div>

    </div>
  </section>

  <script>
    (function() {
      // ── Fleet images supplied server-side by PHP directory scan ──────────
      // PHP scanned /images/fleet/ for all image files at page render time.
      // No client-side probing needed – every URL in this array is confirmed
      // to exist on the server.
      const FLEET_URLS = <?= $fleetImagesJson ?>;
      // ── End Configuration ────────────────────────────────────────────────

      const viewport   = document.getElementById('carouselViewport');
      const dotsWrap   = document.getElementById('carouselDots');
      const currentEl  = document.getElementById('carouselCurrent');
      const totalEl    = document.getElementById('carouselTotal');
      const labelEl    = document.getElementById('carouselLabel');

      let slides = [];
      let dots   = [];
      let active = 0;

      function buildPlaceholder() {
        const slide = document.createElement('div');
        slide.className = 'carousel-slide active';
        slide.innerHTML = `
          <div class="no-img">
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="4" y="18" width="56" height="32" rx="3" stroke="#888" stroke-width="2"/>
              <circle cx="16" cy="50" r="6" stroke="#888" stroke-width="2"/>
              <circle cx="48" cy="50" r="6" stroke="#888" stroke-width="2"/>
              <rect x="4" y="26" width="20" height="12" rx="2" stroke="#888" stroke-width="2"/>
              <path d="M24 30 L36 18 L56 18 L56 30Z" stroke="#888" stroke-width="2"/>
            </svg>
            <span>Add photos to<br>/images/fleet/</span>
          </div>`;
        return slide;
      }

      function goTo(index) {
        slides[active].classList.remove('active');
        dots[active] && dots[active].classList.remove('active');
        active = (index + slides.length) % slides.length;
        slides[active].classList.add('active');
        dots[active] && dots[active].classList.add('active');
        currentEl.textContent = active + 1;
        if (labelEl && slides[active].dataset.filename) {
          labelEl.textContent = slides[active].dataset.filename;
        }
      }

      window.carouselMove = function(dir) { goTo(active + dir); };

      function initCarousel(urls) {
        // Clear placeholder
        viewport.querySelectorAll('.carousel-slide').forEach(el => el.remove());
        dotsWrap.innerHTML = '';
        slides = [];
        dots   = [];

        if (urls.length === 0) {
          const ph = buildPlaceholder();
          viewport.appendChild(ph);
          slides.push(ph);
          totalEl.textContent = '1';
          currentEl.textContent = '1';
          labelEl.textContent = 'Add photos to /images/fleet/';
          return;
        }

        urls.forEach((url, i) => {
          const slide = document.createElement('div');
          slide.className = 'carousel-slide' + (i === 0 ? ' active' : '');
          slide.dataset.filename = url.split('/').pop();
          const img = document.createElement('img');
          img.src = url;
          img.alt = 'Fleet truck photo ' + (i + 1);
          img.loading = 'lazy';
          slide.appendChild(img);
          viewport.appendChild(slide);
          slides.push(slide);

          const dot = document.createElement('button');
          dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
          dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
          dot.addEventListener('click', () => goTo(i));
          dotsWrap.appendChild(dot);
          dots.push(dot);
        });

        totalEl.textContent = urls.length;
        currentEl.textContent = '1';
        if (slides[0]) labelEl.textContent = slides[0].dataset.filename;
      }

      // Initialise directly from server-provided URL list — no probing needed
      initCarousel(FLEET_URLS);

      // Keyboard navigation
      document.addEventListener('keydown', function(e) {
        const fs = document.getElementById('fleet');
        if (!fs) return;
        const rect = fs.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
          if (e.key === 'ArrowLeft')  window.carouselMove(-1);
          if (e.key === 'ArrowRight') window.carouselMove(1);
        }
      });
    })();
  </script>

  <div class="divider-stripe"></div>

  <!-- CONTACT -->
  <section class="contact-section" id="contact">
    <div class="contact-grid">
      <div class="contact-info">
        <div class="section-label">Get in Touch</div>
        <h2 class="section-title">Request a<br>Free Quote</h2>
        <p>Fill out the form and we'll get back to you within the hour during business hours. Or just call — we love talking trucks.</p>

        <div class="contact-detail">
          <div class="contact-detail-icon">📞</div>
          <div class="contact-detail-text">
            <strong>Phone / Dispatch</strong>
            <span>(678) 793-8807 or (678) 793-0711</span>
          </div>
        </div>
        <div class="contact-detail">
          <div class="contact-detail-icon">✉️</div>
          <div class="contact-detail-text">
            <strong>Email</strong>
            <span><a href="mailto:dispatch@economaxtrucking.com" style="color:inherit;text-decoration:none;">dispatch@economaxtrucking.com</a></span>
          </div>
        </div>
        <div class="contact-detail">
          <div class="contact-detail-icon">📍</div>
          <div class="contact-detail-text">
            <strong>Yard Address</strong>
            <span>3343 Lathenview Ct, NO, Alpharetta, GA, 30004, USA</span>
          </div>
        </div>
        <div class="contact-detail">
          <div class="contact-detail-icon">🕐</div>
          <div class="contact-detail-text">
            <strong>Dispatch Hours</strong>
            <span>Mon–Sat 5AM–8PM · Sun by Appointment</span>
          </div>
        </div>
      </div>

      <div class="contact-form">
        <?php if ($formSuccess): ?>
          <div style="background:#1a3a1a;border-left:4px solid #4caf50;padding:20px 24px;margin-bottom:24px;font-family:'Barlow Condensed',sans-serif;font-size:1rem;color:#b6f5b6;letter-spacing:0.04em;">
            ✅ <?= htmlspecialchars($formMessage, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php elseif ($formError): ?>
          <div style="background:#3a1a1a;border-left:4px solid #f44336;padding:20px 24px;margin-bottom:24px;font-family:'Barlow Condensed',sans-serif;font-size:1rem;color:#ffb3b3;letter-spacing:0.04em;">
            ⚠️ <?= htmlspecialchars($formMessage, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="post" action="#contact" novalidate>
          <input type="hidden" name="quote_submit" value="1" />

          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name <span style="color:var(--yellow)">*</span></label>
              <input type="text" id="first_name" name="first_name" placeholder="John" required
                     value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="form-group">
              <label for="last_name">Last Name <span style="color:var(--yellow)">*</span></label>
              <input type="text" id="last_name" name="last_name" placeholder="Smith" required
                     value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone <span style="color:var(--yellow)">*</span></label>
              <input type="tel" id="phone" name="phone" placeholder="(555) 000-0000" required
                     value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="form-group">
              <label for="email">Email <span style="color:var(--yellow)">*</span></label>
              <input type="email" id="email" name="email" placeholder="you@company.com" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
          </div>

          <div class="form-group">
            <label for="company">Company / Project Name</label>
            <input type="text" id="company" name="company" placeholder="ABC Construction"
                   value="<?= htmlspecialchars($_POST['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
          </div>

          <div class="form-group">
            <label for="material">Material Type</label>
            <?php
            $materials = [
                ''                    => '— Select material —',
                'Aggregate / Gravel'  => 'Aggregate / Gravel',
                'Crushed Stone'       => 'Crushed Stone',
                'Topsoil / Fill'      => 'Topsoil / Fill',
                'Sand'                => 'Sand',
                'Asphalt / Millings'  => 'Asphalt / Millings',
                'Demolition Debris'   => 'Demolition Debris',
                'Coal / Fuel Material'=> 'Coal / Fuel Material',
                'Other'               => 'Other',
            ];
            $selectedMaterial = $_POST['material'] ?? '';
            ?>
            <select id="material" name="material">
              <?php foreach ($materials as $val => $label): ?>
                <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                  <?= $selectedMaterial === $val ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="loads">Estimated Loads</label>
              <input type="text" id="loads" name="loads" placeholder="e.g. 10 loads"
                     value="<?= htmlspecialchars($_POST['loads'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="form-group">
              <label for="desired_date">Desired Date</label>
              <input type="date" id="desired_date" name="desired_date"
                     value="<?= htmlspecialchars($_POST['desired_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            </div>
          </div>

          <div class="form-group">
            <label for="notes">Project Details / Notes</label>
            <textarea id="notes" name="notes"
                      placeholder="Delivery address, access notes, special requirements..."><?= htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <button type="submit" class="form-submit">Send My Quote Request →</button>
        </form>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="footer-logo">Econo<span>Max</span>, LLC</div>
    <ul class="footer-links">
      <li><a href="#services">Services</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#fleet">Fleet</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <p class="footer-copy">&copy; <?= date('Y') ?> EconoMax, LLC &middot; Licensed &amp; Insured &middot; Alpharetta, GA</p>
  </footer>

</body>
</html>
