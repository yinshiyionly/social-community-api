{{-- 负向情绪数据邮箱预警通知模版一 --}}
    <!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['subject'] ?? '负向情绪数据邮箱预警通知' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header {
            background-color: #dc3545;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .alert-details {
            border: 1px solid #ffc107;
            background-color: #fff3cd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .alert-details p {
            margin: 5px 0;
            line-height: 1.6;
        }

        .detail-label {
            font-weight: bold;
            color: #333;
            display: inline-block;
            width: 80px;
        }

        .action-button {
            margin-top: 25px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🚨 {{ $data['subject'] ?? '负向情绪数据邮箱预警通知' }}</h1>
    </div>

    <div class="content">
        <p>系统监控检测到一条负向情绪数据，请立即查看并处理。</p>

        <div class="alert-details">
            <p><span class="detail-label">任务名称:</span> {{ $data['task_name'] ?? 'N/A' }}</p>
            <p><span class="detail-label">预警名称:</span> {{ $data['warn_name'] ?? 'N/A' }}</p>
            <p><span class="detail-label">发布ID:</span> {{ $data['origin_id'] ?? 'N/A' }}</p>
            <p><span class="detail-label">发布标题:</span> {{ $data['title'] ?? 'N/A' }}</p>
            <p>
                <span class="detail-label">发布URL:</span>
                @if(isset($data['url']))
                    {{-- 使用 <a> 标签包裹 URL，并添加 target="_blank" --}}
                    <a href="{{ $data['url'] }}" target="_blank" style="color: #007bff; text-decoration: none;">
                        {{ $data['url'] }}
                    </a>
                @else
                    未提供链接
                @endif
            </p>
            <p><span class="detail-label">发布时间:</span> {{ $data['publish_time'] ?? 'N/A' }}</p>
        </div>

        {{--        <p style="margin-top: 25px;">请点击下方按钮进入系统查看任务详情和日志：</p>--}}

        {{--        <div class="action-button">--}}
        {{--            @if(isset($data['link']))--}}
        {{--                <a href="{{ $data['link'] }}" class="button">立即处理任务</a>--}}
        {{--            @else--}}
        {{--                <span style="color: #6c757d;">（未提供链接）</span>--}}
        {{--            @endif--}}
        {{--        </div>--}}

        <p style="margin-top: 30px;">感谢您的关注，如有疑问，请联系技术支持。</p>
    </div>

    <div class="footer">
        <p>此邮件为系统自动发送，请勿直接回复。</p>
    </div>
</div>
</body>
</html>
