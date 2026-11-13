<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if(isset($_GET['lang'])){
        if($_GET['lang'] !== ""){
            pp_set_lang($_GET['lang']);
?>
            <script>
                location.href = '?lang=';
            </script>
<?php
            exit();
        }
    }

    if(isset($_GET['cancel'])){
        pp_set_transaction_status($data['transaction']['ref'], 'canceled');
?>
        <script>
            location.href = '<?php echo pp_checkout_address();?>';
        </script>
<?php
        exit();
    }

    $pp_gateways_mfs = pp_gateways('mfs', $data);
    $pp_gateways_bank = pp_gateways('bank', $data);
    $pp_gateways_global = pp_gateways('global', $data);

    $primaryColor = !empty($data['options']['primary_color']) && $data['options']['primary_color'] !== '--' ? $data['options']['primary_color'] : '#4f46e5';
    $accentColor  = !empty($data['options']['accent_color']) && $data['options']['accent_color'] !== '--' ? $data['options']['accent_color'] : '#06b6d4';
    $textColor    = !empty($data['options']['text_color']) && $data['options']['text_color'] !== '--' ? $data['options']['text_color'] : '#ffffff';
    $themeMode    = $data['options']['theme_mode'] ?? 'glassmorphism';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $data['lang']['checkout']?> - <?php echo htmlspecialchars($data['brand']['name']);?></title>
    <link rel="shortcut icon" href="<?php echo $data['brand']['favicon'];?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo pp_assets('head'); ?>

    <?php
        $seoTitle = trim($data['options']['seo_title'] ?? '');
        $seoDesc  = trim($data['options']['seo_description'] ?? '');
        $seoKey   = trim($data['options']['seo_keywords'] ?? '');
        $analyticsCode = trim($data['options']['analytics_code'] ?? '');

        if ($seoTitle !== '' && $seoTitle !== '--') {
            echo '<title>' . htmlspecialchars($seoTitle) . '</title>' . PHP_EOL;
            echo '<meta name="title" content="' . htmlspecialchars($seoTitle) . '">' . PHP_EOL;
            echo '<meta property="og:title" content="' . htmlspecialchars($seoTitle) . '">' . PHP_EOL;
        }

        if ($seoDesc !== '' && $seoDesc !== '--') {
            echo '<meta name="description" content="' . htmlspecialchars($seoDesc) . '">' . PHP_EOL;
            echo '<meta property="og:description" content="' . htmlspecialchars($seoDesc) . '">' . PHP_EOL;
        }

        if ($seoKey !== '' && $seoKey !== '--') {
            echo '<meta name="keywords" content="' . htmlspecialchars($seoKey) . '">' . PHP_EOL;
        }

        if ($analyticsCode !== '' && $analyticsCode !== '--') {
            echo $analyticsCode;
        }

        $bgStyle = 'background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 50%, #f1f5f9 100%);';
        if (!empty($data['options']['enable_bg_image']) && $data['options']['enable_bg_image'] === 'enabled' && !empty($data['options']['background_image'])) {
            $bgImage = $data['options']['background_image'];
            $bgStyle = "background-image: url('{$bgImage}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;";
        }
    ?>

    <style>
        :root {
            --mp-primary: <?php echo $primaryColor; ?>;
            --mp-accent: <?php echo $accentColor; ?>;
            --mp-text-btn: <?php echo $textColor; ?>;
            --mp-card-bg: rgba(255, 255, 255, 0.94);
            --mp-card-border: rgba(226, 232, 240, 0.9);
            --mp-card-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07), 0 0 1px 1px rgba(0, 0, 0, 0.04);
            --mp-radius: 20px;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            color: #1e293b;
            <?= $bgStyle ?>
            margin: 0;
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .mp-container {
            max-width: 580px;
            width: 100%;
            margin: 0 auto;
        }

        .mp-card {
            background: var(--mp-card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--mp-card-border);
            border-radius: var(--mp-radius);
            box-shadow: var(--mp-card-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .mp-topbar {
            padding: 14px 20px;
            background: rgba(248, 250, 252, 0.8);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mp-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .mp-amount-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 5px 12px;
            background: #f1f5f9;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            flex-shrink: 1;
            min-width: 0;
        }

        .mp-amount-badge span.amount {
            color: var(--mp-primary);
            font-size: 0.95rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .mp-icon-btn:hover {
            background: var(--mp-primary);
            border-color: var(--mp-primary);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px <?php echo pp_hexToRgba($primaryColor, 0.25); ?>;
        }

        .mp-icon-btn.active {
            background: var(--mp-primary);
            border-color: var(--mp-primary);
            color: #ffffff;
        }

        .mp-body {
            padding: 24px 24px;
        }

        .mp-brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .mp-logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }

        .mp-brand-logo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.12);
        }

        .mp-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }

        .mp-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: <?php echo pp_hexToRgba($primaryColor, 0.08); ?>;
            color: var(--mp-primary);
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 20px;
        }

        .mp-nav-pills {
            display: flex;
            gap: 8px;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 14px;
            margin-bottom: 20px;
        }

        .mp-nav-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border: none;
            background: transparent;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .mp-nav-btn svg {
            width: 18px;
            height: 18px;
        }

        .mp-nav-btn.active {
            background: #ffffff;
            color: var(--mp-primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .mp-gateway-card {
            background: #ffffff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 110px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .mp-gateway-card:hover {
            border-color: var(--mp-primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -8px <?php echo pp_hexToRgba($primaryColor, 0.2); ?>;
        }

        .mp-gateway-logo-container {
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .mp-gateway-logo {
            max-height: 40px;
            max-width: 90px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.04));
        }

        .mp-gateway-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            line-height: 1.2;
            margin-top: 2px;
        }

        .mp-amount-banner {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, <?php echo $accentColor; ?> 100%);
            border-radius: 16px;
            padding: 16px 20px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 24px;
            box-shadow: 0 10px 25px -5px <?php echo pp_hexToRgba($primaryColor, 0.35); ?>;
        }

        .mp-amount-label {
            font-size: 0.85rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .mp-amount-val {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .mp-footer-branding {
            text-align: center;
            margin-top: 18px;
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
        }

        .mp-info-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.9rem;
        }

        .mp-info-list li:last-child {
            border-bottom: none;
        }

        .mp-support-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none !important;
            display: block;
            color: #1e293b;
        }

        .mp-support-card:hover {
            border-color: var(--mp-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
            color: var(--mp-primary);
        }

        .mp-support-card svg {
            width: 28px;
            height: 28px;
            margin-bottom: 6px;
            color: var(--mp-primary);
        }

        /* Modern Language Selector Modal */
        .mp-lang-modal .modal-content {
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            background: #ffffff;
        }
        .mp-lang-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: <?php echo pp_hexToRgba($primaryColor, 0.1); ?>;
            color: var(--mp-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .mp-lang-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 8px;
        }
        .mp-lang-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 16px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }
        .mp-lang-card:hover {
            background: #ffffff;
            border-color: var(--mp-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px <?php echo pp_hexToRgba($primaryColor, 0.12); ?>;
        }
        .mp-lang-card.active {
            background: <?php echo pp_hexToRgba($primaryColor, 0.06); ?>;
            border-color: var(--mp-primary);
            box-shadow: 0 0 0 1px var(--mp-primary);
        }
        .mp-lang-card-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .mp-lang-flag {
            font-size: 24px;
            line-height: 1;
            display: inline-block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08));
        }
        .mp-lang-native {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .mp-lang-english {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }
        .mp-lang-check {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mp-lang-card.active .mp-lang-check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--mp-primary);
            color: #ffffff;
        }
        .mp-lang-radio-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            transition: all 0.2s;
        }
        .mp-lang-card:hover .mp-lang-radio-circle {
            border-color: var(--mp-primary);
        }

        @media (max-width: 576px) {
            body { padding: 8px 4px; }
            .mp-card { border-radius: 16px; }
            .mp-topbar {
                padding: 10px 10px;
                gap: 6px;
            }
            .mp-icon-btn {
                width: 32px;
                height: 32px;
                border-radius: 9px;
            }
            .mp-icon-btn svg {
                width: 15px;
                height: 15px;
            }
            .mp-topbar-actions {
                gap: 4px !important;
            }
            .mp-amount-badge {
                padding: 4px 8px;
                font-size: 0.75rem;
                gap: 3px;
            }
            .mp-amount-badge span.amount {
                font-size: 0.82rem;
            }
            .mp-body { padding: 18px 12px; }
        }

        @media (max-width: 380px) {
            .mp-topbar {
                padding: 8px 6px;
                gap: 4px;
            }
            .mp-icon-btn {
                width: 30px;
                height: 30px;
                border-radius: 8px;
            }
            .mp-amount-badge {
                padding: 3px 6px;
                font-size: 0.7rem;
            }
            .mp-amount-badge span.amount {
                font-size: 0.76rem;
            }
            .mp-amount-badge .mp-amount-label {
                display: none;
            }
            .mp-topbar-actions {
                gap: 3px !important;
            }
        }
    </style>
<?php
    /* ---------- data prepared for the two-column checkout layout ---------- */
    $ppcLangCode  = $_SESSION['ui_language'] ?? ($data['brand']['locale']['language'] ?? 'en');
    $ppcLangNames = ['en' => 'English', 'bn' => 'বাংলা', 'hi' => 'हिन्दी', 'ur' => 'اردو', 'ar' => 'العربية'];
    $ppcLangName  = $ppcLangNames[$ppcLangCode] ?? strtoupper((string)$ppcLangCode);

    $ppcI18n = [
        'en' => [
            'trx' => 'Trx ID', 'expires' => 'Expires in', 'expired' => 'Session expired.', 'refresh' => 'Restart timer',
            'subtotal' => 'Subtotal', 'discount' => 'Discount', 'total' => 'Total',
            'customer' => 'Customer Details', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone',
            'support' => 'Support', 'options' => 'Payment Options', 'all' => 'All Options', 'mfs' => 'MFS', 'more' => 'More',
            'pay' => 'Pay', 'cancel' => 'Cancel and return to merchant',
            'none' => 'No payment method is available right now.', 'no_support' => 'No support contact has been added yet.',
        ],
        'bn' => [
            'trx' => 'ট্রান্স আইডি', 'expires' => 'মেয়াদ শেষ হবে', 'expired' => 'সেশনের মেয়াদ শেষ।', 'refresh' => 'টাইমার রিস্টার্ট',
            'subtotal' => 'সাবটোটাল', 'discount' => 'ডিসকাউন্ট', 'total' => 'মোট',
            'customer' => 'কাস্টমারের তথ্য', 'name' => 'নাম', 'email' => 'ইমেইল', 'phone' => 'ফোন',
            'support' => 'সাপোর্ট', 'options' => 'পেমেন্ট অপশন', 'all' => 'সব অপশন', 'mfs' => 'MFS', 'more' => 'আরও',
            'pay' => 'পেমেন্ট', 'cancel' => 'বাতিল করে মার্চেন্টে ফিরে যান',
            'none' => 'এই মুহূর্তে কোনো পেমেন্ট মেথড নেই।', 'no_support' => 'এখনো কোনো সাপোর্ট যোগাযোগ যোগ করা হয়নি।',
        ],
    ];
    $T = $ppcI18n[$ppcLangCode] ?? $ppcI18n['en'];

    /* untouched stock indigo -> use the blue from the design; any custom colour is respected */
    $ppcPrimary = ($primaryColor === '#4f46e5') ? '#2563eb' : $primaryColor;
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$ppcPrimary)) { $ppcPrimary = '#2563eb'; }

    $ppcBg = 'background-color:#eaf3ff;background-image:linear-gradient(to right,rgba(148,163,184,.16) 1px,transparent 1px),linear-gradient(to bottom,rgba(148,163,184,.16) 1px,transparent 1px);background-size:330px 430px;background-position:center top;';
    if (!empty($data['options']['enable_bg_image']) && $data['options']['enable_bg_image'] === 'enabled' && !empty($data['options']['background_image'])) {
        $ppcBg = $bgStyle;
    }

    $ppcFmt      = function($v){ return preg_replace('/\.00$/', '', (string)money_round($v, 2)); };
    $ppcCur      = (string)($data['transaction']['currency'] ?? '');
    $ppcAmountN  = (float)($data['transaction']['amount'] ?? 0);
    $ppcDiscountNum = (float)($data['transaction']['discount_amount'] ?? 0);
    $ppcTotal    = $ppcFmt(max(0, $ppcAmountN - $ppcDiscountNum)) . ' ' . $ppcCur;
    $ppcSubtotal = $ppcFmt($ppcAmountN) . ' ' . $ppcCur;
    $ppcDiscount = $ppcFmt($ppcDiscountNum) . ' ' . $ppcCur;
    $ppcRef      = htmlspecialchars((string)($data['transaction']['ref'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ppcBase     = pp_checkout_address();
    $ppcSupport  = $data['brand']['support'] ?? [];

    $ppcCi = $data['transaction']['customer_info'] ?? [];
    if (is_string($ppcCi)) { $ppcCi = json_decode($ppcCi, true); }
    if (!is_array($ppcCi)) { $ppcCi = []; }
    $ppcClean = function($v){ $v = trim((string)$v); return ($v === '--') ? '' : $v; };
    $ppcName  = $ppcClean($ppcCi['name'] ?? '');
    $ppcEmail = $ppcClean($ppcCi['email'] ?? '');
    $ppcPhone = $ppcClean($ppcCi['mobile'] ?? '');

    $ppcMfs = [];
    if ($pp_gateways_mfs['status'] === true && !empty($pp_gateways_mfs['gateway'])) {
        foreach ($pp_gateways_mfs['gateway'] as $r) { $r['_cat'] = 'mfs'; $ppcMfs[] = $r; }
    }
    $ppcMore = [];
    foreach ([$pp_gateways_bank, $pp_gateways_global] as $grp) {
        if ($grp['status'] === true && !empty($grp['gateway'])) {
            foreach ($grp['gateway'] as $r) { $r['_cat'] = 'more'; $ppcMore[] = $r; }
        }
    }

    $ppcCfg = json_encode([
        'base' => $ppcBase,
        'ref'  => (string)($data['transaction']['ref'] ?? ''),
        'window' => 900,
        't' => ['expires' => $T['expires'], 'expired' => $T['expired'], 'refresh' => $T['refresh']],
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
    <style>
        /* ===== Two-column checkout (summary left, payment options right) ===== */
        html body.ppc-body{<?php echo $ppcBg; ?> margin:0;min-height:100vh;display:block;font-family:'Inter','Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;color:#0f172a}
        .ppc-wrap{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px 16px}
        .ppc-card{width:100%;max-width:1000px;background:#fff;border-radius:32px;box-shadow:0 30px 60px -20px rgba(30,64,175,.18),0 0 0 1px rgba(148,163,184,.10);display:flex;overflow:hidden;min-height:700px}
        .ppc-left{flex:0 0 43.5%;max-width:43.5%;padding:34px 28px 26px;border-right:1px solid #e8edf3;display:flex;flex-direction:column;justify-content:space-between}
        .ppc-right{flex:1;padding:34px 30px 30px;min-width:0}
        .ppc-merchant{display:flex;align-items:flex-start;gap:14px}
        .ppc-logo{width:48px;height:48px;border-radius:12px;object-fit:cover;background:#f8fafc;border:1px solid #eef1f5;flex:0 0 48px}
        .ppc-m-info{flex:1;min-width:0}
        .ppc-m-name{font-weight:600;font-size:16px;line-height:1.25;color:#0f172a;word-break:break-word}
        .ppc-m-ref{font-size:12px;color:#94a3b8;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:210px}
        .ppc-m-exp{font-size:12px;color:#94a3b8;margin-top:6px}
        .ppc-m-exp.ppc-expired{color:#dc2626;font-weight:600}
        .ppc-m-exp a{color:#2563eb;text-decoration:underline;cursor:pointer;margin-left:4px}
        .ppc-m-amt{font-weight:700;font-size:19px;white-space:nowrap;padding-top:2px}
        .ppc-sum{margin-top:26px}
        .ppc-line{display:flex;justify-content:space-between;align-items:center;font-size:15px;padding:9px 0;color:#1e293b}
        .ppc-line.ppc-total{border-top:1px solid #e8edf3;margin-top:6px;padding-top:15px}
        .ppc-line.ppc-total span:first-child{font-weight:500}
        .ppc-cust{margin-top:34px;padding-top:20px;border-top:1px solid #e8edf3}
        .ppc-cust-title{font-size:12px;letter-spacing:.09em;text-transform:uppercase;color:#64748b;margin-bottom:10px;font-weight:500}
        .ppc-cust-row{display:flex;justify-content:space-between;gap:14px;font-size:13.5px;padding:5px 0}
        .ppc-cust-row span:first-child{color:#64748b}
        .ppc-cust-row span:last-child{color:#0f172a;font-weight:500;text-align:right;word-break:break-all}
        .ppc-foot{display:flex;justify-content:space-between;align-items:center;margin-top:30px;font-size:13.5px}
        .ppc-foot a{display:inline-flex;align-items:center;gap:8px;text-decoration:none;cursor:pointer}
        .ppc-foot .ppc-support{color:#64748b}
        .ppc-foot .ppc-lang{color:#2563eb;font-weight:600}
        .ppc-foot svg{width:18px;height:18px}
        .ppc-h{font-size:20px;font-weight:700;margin:0 0 18px;letter-spacing:-.01em}
        .ppc-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
        .ppc-tab{display:inline-flex;align-items:center;gap:8px;height:42px;padding:0 16px;border-radius:10px;border:1px solid #dfe5ec;background:#fff;color:#1e293b;font-weight:600;font-size:14px;cursor:pointer;transition:.15s}
        .ppc-tab svg{width:18px;height:18px}
        .ppc-tab.active{border-color:var(--ppc-primary);background:var(--ppc-tint);color:var(--ppc-primary)}
        .ppc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}
        .ppc-gw{border:1px solid #e2e8f0;border-radius:11px;background:#fff;overflow:hidden;cursor:pointer;transition:.15s;outline:none;display:flex;flex-direction:column}
        .ppc-gw:hover{border-color:#b6c4d9}
        .ppc-gw:focus-visible{box-shadow:0 0 0 3px var(--ppc-ring)}
        .ppc-gw-logo{height:62px;display:flex;align-items:center;justify-content:center;padding:8px}
        .ppc-gw-logo img{max-width:100%;max-height:46px;object-fit:contain}
        .ppc-gw-name{border-top:1px solid #eef1f5;text-align:center;font-size:13px;padding:9px 6px;color:#1e293b;line-height:1.25}
        .ppc-gw.selected{border-color:var(--ppc-primary);background:var(--ppc-tint);box-shadow:0 0 0 1px var(--ppc-primary)}
        .ppc-gw.selected .ppc-gw-name{border-top-color:var(--ppc-ring);color:var(--ppc-primary);font-weight:600}
        .ppc-empty{grid-column:1/-1;text-align:center;color:#64748b;font-size:14px;padding:30px 0}
        .ppc-pay{width:100%;height:56px;border:0;border-radius:14px;margin-top:20px;font-size:17px;font-weight:600;background:#e2e8f0;color:#64748b;cursor:not-allowed;transition:.15s}
        .ppc-pay.ready{background:var(--ppc-primary);color:#fff;cursor:pointer;box-shadow:0 10px 22px -10px var(--ppc-primary)}
        .ppc-pay.ready:hover{filter:brightness(1.07)}
        .ppc-cancel{display:block;text-align:center;margin-top:16px;color:#64748b;font-size:14px;text-decoration:underline;text-underline-offset:3px}
        .ppc-cancel:hover{color:#0f172a}
        .ppc-brand{margin-top:18px;font-size:12px;color:#94a3b8;text-align:center}
        .ppc-support-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#0f172a;margin-bottom:10px;font-size:14px;font-weight:500}
        .ppc-support-item small{display:block;color:#64748b;font-weight:400;word-break:break-all}
        @media (max-width:860px){
            .ppc-wrap{padding:14px 10px;justify-content:flex-start}
            .ppc-card{flex-direction:column;border-radius:24px;min-height:0}
            .ppc-left{flex:none;max-width:100%;border-right:0;border-bottom:1px solid #e8edf3;padding:24px 20px 18px}
            .ppc-right{padding:24px 20px}
            .ppc-cust{margin-top:22px}
            .ppc-foot{margin-top:20px}
        }
        @media (max-width:520px){ .ppc-grid{grid-template-columns:repeat(2,1fr)} .ppc-m-amt{font-size:17px} }
    </style>
</head>
<body class="ppc-body" style="--ppc-primary:<?php echo $ppcPrimary; ?>;--ppc-tint:<?php echo pp_hexToRgba($ppcPrimary, 0.07); ?>;--ppc-ring:<?php echo pp_hexToRgba($ppcPrimary, 0.30); ?>;">

<div class="ppc-wrap">
    <div class="ppc-card">

        <!-- LEFT: order summary + customer -->
        <div class="ppc-left">
            <div>
                <div class="ppc-merchant">
                    <img class="ppc-logo" src="<?php echo htmlspecialchars((string)($data['brand']['favicon'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <div class="ppc-m-info">
                        <div class="ppc-m-name"><?php echo htmlspecialchars((string)($data['brand']['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="ppc-m-ref" title="<?php echo $ppcRef; ?>"><?php echo htmlspecialchars($T['trx'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $ppcRef; ?></div>
                        <div class="ppc-m-exp" id="ppc-exp"><?php echo htmlspecialchars($T['expires'], ENT_QUOTES, 'UTF-8'); ?> --</div>
                    </div>
                    <div class="ppc-m-amt"><?php echo htmlspecialchars($ppcTotal, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="ppc-sum">
                    <div class="ppc-line"><span><?php echo htmlspecialchars($T['subtotal'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ppcSubtotal, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <?php if ($ppcDiscountNum > 0): ?>
                    <div class="ppc-line"><span><?php echo htmlspecialchars($T['discount'], ENT_QUOTES, 'UTF-8'); ?></span><span>- <?php echo htmlspecialchars($ppcDiscount, ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <?php endif; ?>
                    <div class="ppc-line ppc-total"><span><?php echo htmlspecialchars($T['total'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ppcTotal, ENT_QUOTES, 'UTF-8'); ?></span></div>
                </div>

                <?php if ($ppcName !== '' || $ppcEmail !== '' || $ppcPhone !== ''): ?>
                <div class="ppc-cust">
                    <div class="ppc-cust-title"><?php echo htmlspecialchars($T['customer'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ($ppcName !== ''): ?><div class="ppc-cust-row"><span><?php echo htmlspecialchars($T['name'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ppcName, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                    <?php if ($ppcEmail !== ''): ?><div class="ppc-cust-row"><span><?php echo htmlspecialchars($T['email'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ppcEmail, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                    <?php if ($ppcPhone !== ''): ?><div class="ppc-cust-row"><span><?php echo htmlspecialchars($T['phone'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ppcPhone, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="ppc-foot">
                <a class="ppc-support" data-bs-toggle="modal" data-bs-target="#modal-support">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4z"/><path d="M20 14h-2a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-4z"/><path d="M20 18v1a3 3 0 0 1-3 3h-3"/></svg>
                    <?php echo htmlspecialchars($T['support'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a class="ppc-lang" data-bs-toggle="modal" data-bs-target="#modal-language">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l6 6"/><path d="M4 14l6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="M22 22l-5-10-5 10"/><path d="M14 18h6"/></svg>
                    <?php echo htmlspecialchars($ppcLangName, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>

        <!-- RIGHT: payment options -->
        <div class="ppc-right">
            <h2 class="ppc-h"><?php echo htmlspecialchars($T['options'], ENT_QUOTES, 'UTF-8'); ?></h2>

            <div class="ppc-tabs" role="tablist">
                <button type="button" class="ppc-tab active" data-tab="all">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
                    <?php echo htmlspecialchars($T['all'], ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <?php if (!empty($ppcMfs)): ?>
                <button type="button" class="ppc-tab" data-tab="mfs">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="4" width="10" height="16" rx="2"/><path d="M11 5h2"/><path d="M12 17v.01"/></svg>
                    <?php echo htmlspecialchars($T['mfs'], ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <?php endif; ?>
                <?php if (!empty($ppcMore)): ?>
                <button type="button" class="ppc-tab" data-tab="more">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>
                    <?php echo htmlspecialchars($T['more'], ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <?php endif; ?>
            </div>

            <div class="ppc-grid" id="ppc-grid">
                <?php foreach (array_merge($ppcMfs, $ppcMore) as $row): ?>
                <div class="ppc-gw" tabindex="0" role="button"
                     data-cat="<?php echo htmlspecialchars($row['_cat'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-id="<?php echo htmlspecialchars((string)$row['gateway_id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="ppc-gw-logo"><img src="<?php echo htmlspecialchars((string)$row['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)$row['display'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="ppc-gw-name"><?php echo htmlspecialchars((string)$row['display'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($ppcMfs) && empty($ppcMore)): ?>
                <div class="ppc-empty"><?php echo htmlspecialchars($T['none'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>

            <button type="button" class="ppc-pay" id="ppc-pay" disabled><?php echo htmlspecialchars($T['pay'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($ppcTotal, ENT_QUOTES, 'UTF-8'); ?></button>
            <a class="ppc-cancel" href="<?php echo htmlspecialchars($ppcBase, ENT_QUOTES, 'UTF-8'); ?>?cancel"><?php echo htmlspecialchars($T['cancel'], ENT_QUOTES, 'UTF-8'); ?></a>
            <div class="ppc-brand"><?php echo htmlspecialchars((string)($data['options']['watermark_text'] ?? 'Secured by PipraPay'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>
</div>

<!-- Support / FAQ modal -->
<div class="modal fade" id="modal-support" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-1 pt-4 px-4">
                <h5 class="modal-title fw-bold mb-0"><?php echo htmlspecialchars($T['support'], ENT_QUOTES, 'UTF-8'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <?php
                    $ppcContacts = [
                        'email'     => ['Email',     'mailto:%s'],
                        'phone'     => ['Phone',     'tel:%s'],
                        'whatsapp'  => ['WhatsApp',  'https://wa.me/%s'],
                        'telegram'  => ['Telegram',  '%s'],
                        'website'   => ['Website',   '%s'],
                        'messenger' => ['Messenger', '%s'],
                        'fb_page'   => ['Facebook',  '%s'],
                    ];
                    $ppcAnySupport = false;
                    foreach ($ppcContacts as $k => $meta) {
                        $v = trim((string)($ppcSupport[$k] ?? ''));
                        if ($v === '' || $v === '--') { continue; }
                        $ppcAnySupport = true;
                        $href = sprintf($meta[1], $v);
                        if (in_array($k, ['telegram','website','messenger','fb_page'], true) && !preg_match('#^https?://#i', $v)) { continue; }
                ?>
                    <a class="ppc-support-item" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <div><?php echo $meta[0]; ?><small><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></small></div>
                    </a>
                <?php } ?>
                <?php if (!$ppcAnySupport): ?><p class="text-muted small mb-0"><?php echo htmlspecialchars($T['no_support'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

                <?php if (!empty($data['faqs'])): ?>
                <h6 class="fw-bold mt-4 mb-2">FAQ</h6>
                <div class="accordion" id="accordion-ppc">
                    <?php $faqCount = 0; foreach ($data['faqs'] as $faq): $faqCount++; ?>
                    <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?php echo ($faqCount == 1) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ppc-<?php echo $faqCount; ?>"><?php echo htmlspecialchars((string)$faq['title'], ENT_QUOTES, 'UTF-8'); ?></button>
                        </h2>
                        <div id="collapse-ppc-<?php echo $faqCount; ?>" class="accordion-collapse collapse <?php echo ($faqCount == 1) ? 'show' : ''; ?>" data-bs-parent="#accordion-ppc">
                            <div class="accordion-body text-muted small"><?php echo $faq['description']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    <!-- Language Selector Modal -->
    <div class="modal fade mp-lang-modal" id="modal-language" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-2 pt-4 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="mp-lang-icon-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M3.6 9h16.8" /><path d="M3.6 15h16.8" /><path d="M11.5 3a17 17 0 0 0 0 18" /><path d="M12.5 3a17 17 0 0 1 0 18" /></svg>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-dark"><?php echo $data['lang']['select_language'] ?? 'Select Language'; ?></h5>
                            <p class="text-muted small mb-0 mt-1"><?php echo $data['lang']['select_a_language'] ?? 'Choose your preferred language'; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-2">
                    <div class="mp-lang-grid">
                        <?php
                        $currentLang = $_SESSION['ui_language'] ?? ($data['brand']['locale']['language'] ?? 'en');
                        $languagesMeta = [
                            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸', 'code' => 'EN', 'sub' => 'Default'],
                            'bn' => ['name' => 'Bangla', 'native' => 'বাংলা', 'flag' => '🇧🇩', 'code' => 'BN', 'sub' => 'Bengali'],
                            'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'flag' => '🇮🇳', 'code' => 'HI', 'sub' => 'Hindi'],
                            'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'flag' => '🇵🇰', 'code' => 'UR', 'sub' => 'Urdu'],
                            'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦', 'code' => 'AR', 'sub' => 'Arabic'],
                        ];
                        $supportedLanguages = !empty($data['supported_languages']) ? $data['supported_languages'] : [
                            'en' => 'English',
                            'bn' => 'বাংলা',
                            'hi' => 'हिन्दी',
                            'ur' => 'اردو',
                            'ar' => 'العربية',
                        ];
                        foreach ($supportedLanguages as $code => $label):
                            $meta = $languagesMeta[$code] ?? ['name' => $label, 'native' => $label, 'flag' => '🌐', 'code' => strtoupper($code), 'sub' => $label];
                            $isActive = ($currentLang === $code);
                        ?>
                            <div class="mp-lang-card <?= $isActive ? 'active' : '' ?>" onclick="selectModernLanguage('<?= htmlspecialchars($code) ?>')">
                                <div class="mp-lang-card-left">
                                    <span class="mp-lang-flag"><?= $meta['flag'] ?></span>
                                    <div class="mp-lang-info">
                                        <div class="mp-lang-native"><?= htmlspecialchars($meta['native']) ?></div>
                                        <div class="mp-lang-english"><?= htmlspecialchars($meta['sub']) ?></div>
                                    </div>
                                </div>
                                <div class="mp-lang-check">
                                    <?php if ($isActive): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                    <?php else: ?>
                                        <div class="mp-lang-radio-circle"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo pp_assets('footer'); ?>

    <script data-cfasync="false">
    (function(){
        var C = <?php echo $ppcCfg; ?>;
        var tabs = document.querySelectorAll('.ppc-tab'), gws = document.querySelectorAll('.ppc-gw');
        var pay = document.getElementById('ppc-pay'), exp = document.getElementById('ppc-exp');
        var selected = null, expired = false, iv = null;

        function filter(tab){
            for (var i = 0; i < gws.length; i++) {
                var c = gws[i].getAttribute('data-cat');
                gws[i].style.display = (tab === 'all' || tab === c) ? '' : 'none';
            }
        }
        function updatePay(){
            var ok = !!selected && !expired;
            pay.disabled = !ok;
            if (ok) { pay.classList.add('ready'); } else { pay.classList.remove('ready'); }
        }
        function select(g){
            for (var i = 0; i < gws.length; i++) { gws[i].classList.remove('selected'); }
            g.classList.add('selected');
            selected = g.getAttribute('data-id');
            updatePay();
        }

        for (var t = 0; t < tabs.length; t++) {
            tabs[t].addEventListener('click', function(){
                for (var j = 0; j < tabs.length; j++) { tabs[j].classList.remove('active'); }
                this.classList.add('active');
                filter(this.getAttribute('data-tab'));
            });
        }
        for (var k = 0; k < gws.length; k++) {
            (function(g){
                g.addEventListener('click', function(){ select(g); });
                g.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(g); }
                });
            })(gws[k]);
        }
        pay.addEventListener('click', function(){
            if (!selected || expired) { return; }
            location.href = C.base + '?gateway=' + encodeURIComponent(selected);
        });

        /* session countdown (visual; restarts when the customer presses "Restart timer") */
        var key = 'ppc-start:' + C.ref, start = 0;
        try { start = parseInt(sessionStorage.getItem(key) || '0', 10) || 0; } catch(e){}
        if (!start) { start = Date.now(); try { sessionStorage.setItem(key, String(start)); } catch(e){} }

        function tick(){
            var left = C.window - Math.floor((Date.now() - start) / 1000);
            if (left <= 0) {
                expired = true; clearInterval(iv);
                exp.className = 'ppc-m-exp ppc-expired';
                exp.textContent = C.t.expired + ' ';
                var a = document.createElement('a');
                a.textContent = C.t.refresh;
                a.addEventListener('click', function(){
                    try { sessionStorage.removeItem(key); } catch(e){}
                    location.reload();
                });
                exp.appendChild(a);
                updatePay();
                return;
            }
            var s = left % 60;
            exp.textContent = C.t.expires + ' ' + Math.floor(left / 60) + 'm ' + (s < 10 ? '0' : '') + s + 's';
        }
        tick();
        if (!expired) { iv = setInterval(tick, 1000); }
    })();

    function selectModernLanguage(code) {
        if (code) { location.href = '?lang=' + code; }
    }
    function hitLanguage() {
        var language = document.querySelector("#model-languages") ? document.querySelector("#model-languages").value : '';
        if (language) { location.href = '?lang=' + language; }
    }
    </script>
</body>
</html>
