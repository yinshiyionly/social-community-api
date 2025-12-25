{{-- 诽谤类举报邮件模版 --}}
{{-- 用于发送诽谤举报信息给网信办，格式正式 --}}
    <!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['subject'] ?? '撤稿申请函' }}</title>
    <style>
        body {
            font-family: 'SimSun', 'Microsoft YaHei', serif;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
            color: #333;
            line-height: 2;
            font-size: 14px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #c00000;
            margin-bottom: 20px;
        }

        .greeting {
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            margin: 20px 0 10px 0;
        }

        .content-block {
            margin-bottom: 15px;
            text-indent: 2em;
        }

        .info-row {
            margin: 5px 0;
        }

        .info-label {
            font-weight: normal;
        }

        .highlight {
            color: #c00000;
            font-weight: bold;
        }

        .url-link {
            color: #0066cc;
            word-break: break-all;
        }

        .indent {
            text-indent: 2em;
        }

        .no-indent {
            text-indent: 0;
        }

        .law-reference {
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    {{-- 标题 --}}
    <div class="title">【撤稿申请函】</div>

    {{-- 称呼 --}}
    <div class="greeting">
        尊敬的中央网信办审核领导及平台内容审核人员：
    </div>

    {{-- 开头说明 --}}
    <div class="content-block">
        您好！我司非常重视来自消费者涉及我司相关人员的网络意见，无奈出具此函并非对网络声音置若罔闻，但该信息的侵权/诽谤内容已对我司产生不良影响，劳烦您审核后能够予以删除或采取同等效果措施；
    </div>

    <div class="content-block no-indent">
        <span>{{ $data['site_name'] ?? '举报网站名称' }}</span>发布相关不实言论，郑重向贵平台致函如下：
    </div>

    {{-- 一、投诉人信息 --}}
    <div class="section-title">一、投诉人信息</div>
    {{--    举报主体: 公民/法人及其组织--}}
    <div class="info-row">举报主体：{{ $data['report_subject'] ?? '' }}</div>

    @if(isset($data['report_subject']) && $data['report_subject'] == \App\Models\PublicRelation\MaterialDefamation::REPORT_SUBJECT_CITIZEN)
        {{--    report_subject = 公民时为“从业类别” --}}
        <div class="info-row">从业类别：{{ $data['occupation_category'] ?? '从业类别' }}</div>
        <div class="info-row">真实姓名：{{ $data['real_name'] ?? '真实姓名' }}</div>
        <div class="info-row">有效联系电话：{{ $data['contact_phone'] ?? '有效联系电话' }}</div>
        <div class="info-row">有效电子邮件：{{ $data['contact_email'] ?? '有效电子邮件' }}</div>
    @else
        {{--    report_subject = 法人及其组织时为“单位类别”--}}
        <div class="info-row">单位类别：{{ $data['occupation_category'] ?? '单位类别' }}</div>
        <div class="info-row">单位名称：{{ $data['enterprise_name'] ?? '单位名称' }}</div>
        <div class="info-row">联系人姓名：{{ $data['real_name'] ?? '联系人姓名' }}</div>
        <div class="info-row">有效联系电话：{{ $data['contact_phone'] ?? '有效联系电话' }}</div>
        <div class="info-row">有效电子邮件：{{ $data['contact_email'] ?? '有效电子邮件' }}</div>
    @endif

    {{-- 二、举报信息 --}}
    <div class="section-title">二、举报信息</div>
    <div class="info-row">举报网站名称：{{ $data['site_name'] ?? '举报网站名称' }}</div>
    <div class="info-row">详细举报网址：</div>
    @if(isset($data['site_url']) && is_array($data['site_url']) && count($data['site_url']) > 0)
        @foreach($data['site_url'] as $url)
            <div class="info-row"><a href="{{ is_array($url) ? ($url['url'] ?? '') : $url }}" class="url-link"
                                     target="_blank">{{ is_array($url) ? ($url['url'] ?? '') : $url }}</a></div>
        @endforeach
    @else
        <div class="info-row">{{ $data['site_url'] ?? '未填写' }}</div>
    @endif

    {{-- 三、具体举报内容 --}}
    <div class="section-title">三、具体举报内容</div>

    <div class="content-block">
        {!! nl2br(e($data['report_content'] ?? '请填写侵权事实与理由')) !!}
    </div>

    {{-- 附件说明 --}}
    @if(isset($data['attachments']) && is_array($data['attachments']) && count($data['attachments']) > 0)
        <div class="section-title">四、附件材料</div>
        <div class="content-block no-indent">
            @foreach($data['attachments'] as $index => $attachment)
                {{ $index + 1 }}、{{ $attachment['name'] ?? '附件' }}<br>
            @endforeach
        </div>
    @endif

    {{-- 落款 --}}
    <div style="text-align: right; margin-top: 40px;">
        <div>投诉人：{{ $data['real_name'] ?? '投诉人姓名' }}</div>
        <div>日期：{{ $data['date'] ?? date('Y年m月d日') }}</div>
    </div>
</div>
</body>
</html>
