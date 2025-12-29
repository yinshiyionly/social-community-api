{{-- 政治类举报邮件模版 --}}
{{-- 用于发送政治类举报信息给网信办，格式正式 --}}
{{-- 根据 report_platform（网站网页/APP/网络账号）动态渲染不同内容 --}}
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
    {{--<div class="title">【政治类举报函】</div>--}}

    {{-- 称呼 --}}
    <div class="greeting">
        尊敬的中央网信办审核领导及平台内容审核人员：
    </div>

    {{-- 开头说明 --}}
    <div class="content-block">
        您好！本人本人实名举报以下涉及政治类违法违规信息，该信息严重违反国家相关法律法规，恳请您审核后能够予以删除或采取相应处置措施。<br>
        详细举报人信息和举报信息如下，撤稿申请函及其他证据资料见附件
    </div>

    {{-- 一、举报人信息 --}}
    <div class="section-title">一、举报人信息</div>
    <div class="info-row">姓名：{{ $data['human_name'] ?? '举报人姓名' }}</div>
    <div class="info-row">性别：{{ $data['human_gender'] ?? '未知' }}</div>
    <div class="info-row">有效联系电话：{{ $data['human_phone'] ?? '未填写' }}</div>
    <div class="info-row">有效电子邮件：{{ $data['human_email'] ?? '未填写' }}</div>
    <div class="info-row">通讯地址：{{ $data['human_address'] ?? '未填写' }}</div>

    {{-- 二、被举报信息 --}}
    <div class="section-title">二、被举报信息</div>
    <div class="info-row">举报类型：{{ $data['report_type'] ?? '政治类' }}</div>
    <div class="info-row">危害小类：{{ $data['report_sub_type'] ?? '未填写' }}</div>
    <div class="info-row">被举报平台：{{ $data['report_platform'] ?? '未填写' }}</div>

    {{-- 根据被举报平台类型动态渲染不同内容 --}}
    @if($data['report_platform'] === '网站网页')
        {{-- 网站网页类型 --}}
        <div class="info-row">网站名称：{{ $data['site_name'] ?? '未填写' }}</div>
        <div class="info-row">网站网址：</div>
        @if(isset($data['site_url']) && is_array($data['site_url']) && count($data['site_url']) > 0)
            @foreach($data['site_url'] as $url)
                <div class="info-row"><a href="{{ is_array($url) ? ($url['url'] ?? '') : $url }}" class="url-link" target="_blank">{{ is_array($url) ? ($url['url'] ?? '') : $url }}</a></div>
            @endforeach
        @else
            <div class="info-row">{{ $data['site_url'] ?? '未填写' }}</div>
        @endif

    @elseif($data['report_platform'] === 'APP')
        {{-- APP类型 --}}
        <div class="info-row">APP名称：{{ $data['app_name'] ?? '未填写' }}</div>
        <div class="info-row">APP定位：{{ $data['app_location'] ?? '未填写' }}</div>
        <div class="info-row">APP网址：</div>
        @if(isset($data['app_url']) && is_array($data['app_url']) && count($data['app_url']) > 0)
            @foreach($data['app_url'] as $url)
                <div class="info-row"><a href="{{ is_array($url) ? ($url['url'] ?? '') : $url }}" class="url-link" target="_blank">{{ is_array($url) ? ($url['url'] ?? '') : $url }}</a></div>
            @endforeach
        @else
            <div class="info-row">{{ $data['app_url'] ?? '未填写' }}</div>
        @endif

    @elseif($data['report_platform'] === '网络账号')
        {{-- 网络账号类型 --}}
        <div class="info-row">账号平台：{{ $data['account_platform'] ?? '未填写' }}</div>
        {{-- 如果账号平台需要填写平台名称，则显示 --}}
        @if(!empty($data['account_platform_name']))
            <div class="info-row">账号平台名称：{{ $data['account_platform_name'] }}</div>
        @endif
        <div class="info-row">账号性质：{{ $data['account_nature'] ?? '未填写' }}</div>
        <div class="info-row">账号名称：{{ $data['account_name'] ?? '未填写' }}</div>
        <div class="info-row">账号网址：</div>
        @if(isset($data['account_url']) && is_array($data['account_url']) && count($data['account_url']) > 0)
            @foreach($data['account_url'] as $url)
                <div class="info-row"><a href="{{ is_array($url) ? ($url['url'] ?? '') : $url }}" class="url-link" target="_blank">{{ is_array($url) ? ($url['url'] ?? '') : $url }}</a></div>
            @endforeach
        @else
            <div class="info-row">{{ $data['account_url'] ?? '未填写' }}</div>
        @endif
    @endif

    {{-- 三、举报内容 --}}
    <div class="section-title">三、举报内容</div>
    <div class="content-block">
        {!! nl2br(e($data['report_content'] ?? '请填写举报内容')) !!}
    </div>

    {{-- 四、法律依据 --}}
   {{-- <div class="section-title">四、法律依据</div>
    <div class="content-block">
        根据《网络信息内容生态治理规定》第六条，网络信息内容生产者不得制作、复制、发布含有下列内容的违法信息：危害国家安全，泄露国家秘密，颠覆国家政权，破坏国家统一的；损害国家荣誉和利益的；歪曲、丑化、亵渎、否定英雄烈士事迹和精神的；宣扬恐怖主义、极端主义或者煽动实施恐怖活动、极端主义活动的；煽动民族仇恨、民族歧视，破坏民族团结的；破坏国家宗教政策，宣扬邪教和封建迷信的。
    </div>
    <div class="content-block">
        根据《中华人民共和国网络安全法》第十二条，任何个人和组织使用网络应当遵守宪法法律，遵守公共秩序，尊重社会公德，不得危害网络安全，不得利用网络从事危害国家安全、荣誉和利益等活动。
    </div>--}}

    {{-- 五、诉求 --}}
    {{--<div class="section-title">五、诉求</div>
    <div class="content-block">
        综上所述，本人请求贵平台依据相关法律法规，对上述违法违规信息予以删除或采取其他合理有效措施，以维护国家安全和社会公共利益。
    </div>--}}

    {{-- 六、附件材料 --}}
    {{--@if(isset($data['attachments']) && is_array($data['attachments']) && count($data['attachments']) > 0)
    <div class="section-title">六、附件材料</div>
    <div class="content-block no-indent">
        @foreach($data['attachments'] as $index => $attachment)
            {{ $index + 1 }}、{{ $attachment['name'] ?? '附件' }}<br>
        @endforeach
    </div>
    @endif--}}

    {{-- 落款 --}}
    {{--<div style="text-align: right; margin-top: 40px;">
        <div>举报人：{{ $data['human_name'] ?? '举报人姓名' }}</div>
        <div>日期：{{ $data['date'] ?? date('Y年m月d日') }}</div>
    </div>--}}
</div>
</body>
</html>
