<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱配置测试</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 24px;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info-item label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 100px;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>邮箱配置测试成功</h1>
        </div>

        <div class="content">
            <p>恭喜！您的邮箱配置已通过测试，邮件发送功能正常。</p>

            <h3>配置信息</h3>
            <div class="info-item">
                <label>发件邮箱：</label>
                <span>{{ $data['email'] ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>SMTP服务器：</label>
                <span>{{ $data['smtp_host'] ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>SMTP端口：</label>
                <span>{{ $data['smtp_port'] ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>测试时间：</label>
                <span>{{ $data['test_time'] ?? now()->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>

        <div class="footer">
            <p>此邮件为系统自动发送的测试邮件，请勿回复。</p>
        </div>
    </div>
</body>
</html>
