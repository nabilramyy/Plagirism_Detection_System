<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #000080 0%, #0056b3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            text-align: center;
            max-width: 450px;
            position: relative;
        }

        .notification-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: <?= $bgColor ?>;
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: <?= $bgColor ?>;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .icon {
            font-size: 40px;
            color: #fff;
            font-weight: bold;
        }

        .message {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .submessage {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: <?= $bgColor ?>;
            animation: progress <?= $delay / 1000 ?>s linear forwards;
        }

        @keyframes progress {
            from { width: 0%; }
            to   { width: 100%; }
        }
    </style>
</head>
<body>

<div class="notification-card">
    <div class="icon-wrapper">
        <div class="icon"><?= $icon ?></div>
    </div>

    <div class="message"><?= $message ?></div>
    <div class="submessage"><?= $subText ?></div>

    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>
</div>

<script>
    setTimeout(() => {
        window.location.href = <?= json_encode($url) ?>;
    }, <?= (int)$delay ?>);
</script>


</body>
</html>
