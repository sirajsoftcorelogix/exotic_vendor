<?php
/** @var string $awb */
/** @var string $errorMessage */
/** @var string $retryUrl */
/** @var string $downloadUrl */
/** @var int $dispatchId */
/** @var array{message?:string,http_code?:int,api_message?:string}|null $fetchResult */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delhivery Label — Download failed</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f3f4f6;
            color: #111827;
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            text-align: center;
        }
        .icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .awb {
            margin: 0 0 16px;
            font-family: monospace;
            font-size: 15px;
            color: #374151;
        }
        .message {
            margin: 0 0 20px;
            font-size: 14px;
            line-height: 1.5;
            color: #4b5563;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #4f46e5;
            color: #fff;
        }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary {
            background: #fff;
            color: #374151;
            border-color: #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; }
        .hint {
            margin-top: 18px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.45;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon" aria-hidden="true">!</div>
        <h1>Label download failed</h1>
        <p class="awb">AWB <?= htmlspecialchars($awb) ?></p>
        <p class="message"><?= htmlspecialchars($errorMessage) ?></p>

        <div class="actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($retryUrl) ?>">Retry download</a>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($downloadUrl) ?>">Retry as file download</a>
            <button type="button" class="btn btn-secondary" onclick="window.close()">Close</button>
        </div>

        <p class="hint">
            Retry fetches a fresh PDF from Delhivery. If it keeps failing, confirm the AWB in Delhivery One
            or re-dispatch the box.
        </p>
    </div>
</body>
</html>
