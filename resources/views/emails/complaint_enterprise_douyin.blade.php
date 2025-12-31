{{-- 企业类举报邮件模版 --}}
{{-- 用于发送企业类举报信息给抖音官方，格式正式 --}}
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['subject'] ?? '账号内容侵权投诉' }}</title>
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
    {{--<div class="title">【撤稿申请函】</div>--}}

    {{-- 称呼 --}}
    <div class="greeting">
        <strong>北京抖音科技有限公司：</strong>
    </div>

    {{-- 开头说明 --}}
    <div class="content-block">
        您好！本人实名举报<span>{{ $data['account_name'] ?? '被投诉账号名称' }}</span>在<span>{{ $data['site_name'] ?? '举报网站名称' }}</span>发布相关不实言论，郑重向贵平台致函；<br>
        详细举报人信息和举报信息如下，撤稿申请函及其他证据资料见附件
    </div>

    {{-- 一、投诉人信息 --}}
    <div class="section-title">一、投诉人信息</div>
    <div class="info-row">企业名称：{{ $data['company_name'] ?? '企业名称' }}</div>
    <div class="info-row">营业执照或组织机构代码证：见附件</div>
    <div class="info-row">企业类型：{{ $data['company_type'] ?? '企业类型' }}</div>
    <div class="info-row">企业性质：{{ $data['company_nature'] ?? '企业性质' }}</div>
    <div class="info-row">行业分类：{{ $data['company_industry'] ?? '行业分类' }}</div>
    <div class="info-row">联系人身份：{{ $data['company_contact_identity'] ?? '联系人身份' }}</div>
    <div class="info-row">联系人姓名：{{ $data['company_contact_name'] ?? '联系人姓名' }}</div>
    <div class="info-row">有效联系电话：{{ $data['company_contact_phone'] ?? '有效联系电话' }}</div>
    <div class="info-row">有效电子邮件：{{ $data['company_contact_email'] ?? '有效电子邮件' }}</div>

    {{-- 二、被投诉人信息 --}}
    <div class="section-title">二、被投诉人信息</div>
    <div class="info-row">举报网站名称：{{ $data['site_name'] ?? '举报网站名称' }}</div>
    <div class="info-row">举报账号名称：{{ $data['account_name'] ?? '举报账号名称' }}</div>
    <div class="info-row">详细举报网址：</div>
    @if(isset($data['item_url']) && is_array($data['item_url']) && count($data['item_url']) > 0)
        @foreach($data['item_url'] as $url)
            <div class="info-row"><a href="{{ is_array($url) ? ($url['url'] ?? '') : $url }}" class="url-link" target="_blank">{{ is_array($url) ? ($url['url'] ?? '') : $url }}</a></div>
        @endforeach
    @else
        <div class="info-row">{{ $data['item_url'] ?? '未填写' }}</div>
    @endif
    <div class="info-row">证据种类：</div>
    @if(isset($data['proof_type']) && is_array($data['proof_type']) && count($data['proof_type']) > 0)
        @foreach($data['proof_type'] as $proofTypeItem)
            <div class="info-row">{{ $proofTypeItem }}</div>
        @endforeach
    @else
        <div class="info-row">{{ $data['proof_type'] ?? '未填写' }}</div>
    @endif

    {{-- 三、具体举报内容 --}}
    <div class="section-title">三、具体举报内容</div>

    <div class="content-block">
        {!! nl2br(e($data['report_content'] ?? '请填写侵权事实与理由')) !!}
    </div>

</div>
</body>
</html>
