{{-- 企业类举报邮件模版 --}}
{{-- 用于发送企业类举报信息给网信办，格式正式 --}}
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
        {{ $data['company_name'] ?? '投诉公司名称' }}，对"<span>{{ $data['account_name'] ?? '被投诉内容标题' }}</span>"在<span>{{ $data['site_name'] ?? '举报网站名称' }}</span>发布相关不实言论，郑重向贵平台致函如下：
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
    <div class="content-block no-indent">
        我方「{{ $data['company_name'] ?? '投诉公司名称' }}」系依法注册的合法主体，现依据《民法典》《网络安全法》《网络信息内容生态治理规定》及《网站平台受理处置涉企网络侵权信息举报工作规范》等相关法律法规，请求贵司协助及贵平台涉及我方权益的侵权信息提出删除请求。
    </div>

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

    {{-- 三、侵权事实与理由 --}}
    <div class="section-title">三、侵权事实与理由</div>

    <div class="content-block">
        {!! nl2br(e($data['report_content'] ?? '请填写侵权事实与理由')) !!}
    </div>

    {{-- 法律依据 --}}
    <div class="section-title">四、法律依据</div>
    <div class="content-block">
        根据《网络信息内容生态治理规定》第六条，禁止制作、发布侵害他人名誉、隐私等合法权益的违法信息。依据《网站平台受理处置涉企网络侵权信息举报工作规范》第四条，平台应重点受理处置虚假信息、侵害名誉权、诽谤性等损害企业合法权益的信息；涉事内容符合上述情形。
    </div>
    <div class="content-block">
        根据《民法典》第一千零二十四条关于名誉权保护的规定，任何组织或者个人不得以侮辱、诽谤等方式侵害他人的名誉权。
    </div>

    {{-- 诉求 --}}
    <div class="section-title">五、诉求</div>
    <div class="content-block">
        综上所述，我方请求贵平台依据相关法律法规，对上述侵权信息予以删除或采取其他合理有效措施，以维护我方合法权益。
    </div>

    {{-- 附件说明 --}}
    @if(isset($data['attachments']) && is_array($data['attachments']) && count($data['attachments']) > 0)
    <div class="section-title">六、附件材料</div>
    <div class="content-block no-indent">
        @foreach($data['attachments'] as $index => $attachment)
            {{ $index + 1 }}、{{ $attachment['name'] ?? '附件' }}<br>
        @endforeach
    </div>
    @endif

    {{-- 落款 --}}
    <div style="text-align: right; margin-top: 40px;">
        <div>投诉人：{{ $data['company_name'] ?? '投诉公司名称' }}</div>
        <div>日期：{{ $data['date'] ?? date('Y年m月d日') }}</div>
    </div>
</div>
</body>
</html>
