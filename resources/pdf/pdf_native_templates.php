<?php

declare(strict_types=1);

require_once __DIR__.'/pdf_templates.php';

/**
 * Native Sena LPD document system.
 *
 * Every document is laid out by mPDF from semantic HTML. The historical PDF
 * file is retained as a content reference only and is never used as a page
 * background, so text can wrap naturally without coordinate overlays.
 */
function pdf_native_template(string $key, array $project, array $students): string
{
    return match ($key) {
        'blank_pack' => pdf_native_blank_pack($project),
        'pt_1' => pdf_pt1(),
        'pt_2' => pdf_pt2($project),
        'pt_3' => pdf_pt3($project),
        'pt_4' => pdf_pt4($project),
        'pt_5' => pdf_pt5($project),
        'pt_6' => pdf_pt6($project, $students),
        'pt_7' => pdf_pt7($project, $students),
        'pt_8' => pdf_pt8($project, $students),
        'pt_9_1' => pdf_pt91($project, $students),
        'pt_9_2' => pdf_pt92($project),
        'pt_10_1' => pdf_pt10($project, $students, false),
        'pt_10_2' => pdf_pt10($project, $students, true),
        'pt_11' => pdf_pt11($project, $students),
        'pt_12' => pdf_pt12($project),
        'pt_13' => pdf_pt13($project, $students),
        'pt_14' => pdf_pt14($project, pdf_native_passed_students($students)),
        'pt_15' => pdf_pt15($project, $students),
        'pt_16' => pdf_pt16($project, $students),
        'pt_17' => pdf_pt17($project),
        'pt_18' => pdf_native_receipt($project),
        'pt_19' => pdf_native_payment_pack($project),
        'pt_20' => pdf_native_follow_up($project, $students),
        'pt_21' => pdf_native_announcement($project),
        'pt_22' => pdf_native_mou($project),
        'time_blank' => pdf_time_blank($project, $students, false),
        'time_blank_two' => pdf_time_blank($project, $students, true),
        'lecturer_time_blank' => pdf_lecturer_time_blank($project),
        'photo_material' => pdf_photo_report($project, 'material'),
        'photo_activity' => pdf_photo_report($project, 'activity'),
        'open_group' => pdf_open_group($project, $students),
        'invite_lecturer' => pdf_pt4($project),
        default => '<div class="title">ไม่พบแม่แบบเอกสาร</div>',
    };
}

/** @return array<int, array<string, mixed>> */
function pdf_native_passed_students(array $students): array
{
    if ($students === []) {
        return [];
    }

    return array_values(array_filter($students, static function (array $student): bool {
        $score = (float) ($student['knowledge_score'] ?? 0)
            + (float) ($student['skill_score'] ?? 0)
            + (float) ($student['attribute_score'] ?? 0);

        return $score >= 60;
    }));
}

function pdf_native_blank_pack(array $project): string
{
    $keys = [
        'pt_1', 'pt_2', 'pt_3', 'pt_4', 'pt_5', 'pt_6', 'pt_7', 'pt_8',
        'pt_9_1', 'pt_9_2', 'pt_10_2', 'pt_11', 'pt_12', 'pt_13', 'pt_14',
        'pt_15', 'pt_16', 'pt_17', 'pt_18', 'pt_19', 'pt_20', 'pt_21', 'pt_22',
    ];
    $html = '';

    foreach ($keys as $index => $key) {
        if ($index > 0) {
            $html .= $key === 'pt_14'
                ? '<pagebreak orientation="L" sheet-size="A4-L" margin-left="20mm" margin-right="20mm" margin-top="15mm" margin-bottom="15mm" />'
                : '<pagebreak orientation="P" sheet-size="A4" margin-left="30mm" margin-right="20mm" margin-top="20mm" margin-bottom="20mm" />';
        }

        $html .= pdf_native_template($key, $project, []);
    }

    return $html;
}

function pdf_native_receipt(array $project, string $copyLabel = ''): string
{
    $amount = (float) ($project['lecturer_cost'] ?? 0);
    $hours = max(1, (int) ($project['course_hours'] ?? 1));
    $rate = $amount / $hours;
    $copy = $copyLabel !== '' ? '<div class="copy-label">'.pdf_h($copyLabel).'</div>' : '';

    return '<section class="official-form">'.$copy.pdf_form_code('พต. 18')
        .'<div class="title">ใบสำคัญรับเงิน</div>'
        .'<div class="subtitle">'.pdf_h(SENA_ORG['district_full']).'</div>'
        .'<p class="right document-date">วันที่ '.pdf_h(pdf_thai_date(date('Y-m-d'))).'</p>'
        .'<p>ข้าพเจ้า <span class="line-long">'.pdf_h(pval($project, 'lecturer_name', '')).'</span> '
        .'เลขประจำตัวประชาชน <span class="line-long">'.pdf_h(pval($project, 'lecturer_id_card', '')).'</span><br>'
        .'ที่อยู่ <span class="line-long wide">'.pdf_h(pval($project, 'lecturer_address', '')).'</span><br>'
        .'ได้รับเงินจาก '.pdf_h(SENA_ORG['district_full']).' ดังรายการต่อไปนี้</p>'
        .'<table class="grid receipt-table"><thead><tr><th>รายการ</th><th class="money-column">จำนวนเงิน (บาท)</th></tr></thead>'
        .'<tbody><tr><td>ค่าตอบแทนวิทยากร หลักสูตร '.pdf_h(pval($project, 'course_name')).'<br>'
        .'สถานที่จัด '.pdf_h(pval($project, 'place')).' '.pdf_h(pval($project, 'address')).'<br>'
        .'ระหว่างวันที่ '.pdf_h(pdf_thai_date($project['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($project['end_date'] ?? null)).'<br>'
        .'จำนวน '.pdf_h((string) $hours).' ชั่วโมง อัตราชั่วโมงละ '.pdf_money($rate).' บาท</td>'
        .'<td class="right">'.pdf_money($amount).'</td></tr>'
        .'<tr><th class="right">รวมเงินทั้งสิ้น</th><th class="right">'.pdf_money($amount).'</th></tr></tbody></table>'
        .'<p>จำนวนเงินเป็นตัวอักษร ( '.pdf_h(pdf_baht_text($amount)).' )</p>'
        .'<table class="two-signatures signature-row"><tr><td>ลงชื่อ ........................................................ ผู้รับเงิน<br>'
        .'( '.pdf_h(pval($project, 'lecturer_name', '........................................................')).' )</td>'
        .'<td>ลงชื่อ ........................................................ '.pdf_h(SENA_ORG['payer_position']).'<br>( '.pdf_h(SENA_ORG['payer_name']).' )</td></tr></table>'
        .'<p class="note"><b>หมายเหตุ</b> แนบสำเนาบัตรประจำตัวประชาชนของผู้รับเงินและหนังสือเชิญวิทยากร</p>'
        .'</section>';
}

function pdf_native_payment_pack(array $project): string
{
    $amount = (float) ($project['lecturer_cost'] ?? 0);

    $evidence = '<section class="official-form">'.pdf_form_code('พต. 19')
        .'<div class="title">หลักฐานการจ่ายเงินค่าตอบแทนวิทยากร</div>'
        .'<div class="subtitle">'.pdf_h(SENA_ORG['district_full']).'</div>'
        .'<p class="thai-distributed">ข้าพเจ้า '.pdf_h(pval($project, 'lecturer_name', '')).' '
        .'ได้รับเงินค่าตอบแทนวิทยากร หลักสูตร '.pdf_h(pval($project, 'course_name')).' จำนวน '
        .pdf_h(pval($project, 'course_hours')).' ชั่วโมง ณ '.pdf_h(pval($project, 'place')).' '
        .'ระหว่างวันที่ '.pdf_h(pdf_thai_date($project['start_date'] ?? null)).' ถึง '
        .pdf_h(pdf_thai_date($project['end_date'] ?? null)).' เป็นเงิน '.pdf_money($amount).' บาท '
        .'( '.pdf_h(pdf_baht_text($amount)).' ) ถูกต้องครบถ้วนแล้ว</p>'
        .'<div class="signature large-signature">ลงชื่อ ........................................................ ผู้รับเงิน<br>'
        .'( '.pdf_h(pval($project, 'lecturer_name', '........................................................')).' )<br>'
        .'วันที่ '.pdf_h(pdf_thai_date(date('Y-m-d'))).'</div>'
        .'<div class="approval-box"><b>คำรับรองของเจ้าหน้าที่</b><br><br>ขอรับรองว่าได้ตรวจสอบหลักฐานและจ่ายเงินถูกต้องตามรายการข้างต้นแล้ว'
        .'<table class="two-signatures"><tr><td>ลงชื่อ ........................................................ '.pdf_h(SENA_ORG['payer_position']).'<br>'
        .'( '.pdf_h(SENA_ORG['payer_name']).' )</td><td>ลงชื่อ ........................................................ '.pdf_h(SENA_ORG['certifier_position']).'<br>'
        .'( '.pdf_h(SENA_ORG['certifier_name']).' )</td></tr></table></div></section>';

    $ktb = '<section class="official-form">'.pdf_form_code('พต. 19')
        .'<div class="title">แบบแจ้งข้อมูลการรับเงินโอนผ่านระบบ KTB Corporate Online</div>'
        .'<p class="center">'.pdf_h(SENA_ORG['district_full']).'</p>'
        .'<table class="form-table"><tr><th>เรียน</th><td>ผู้อำนวยการ'.pdf_h(SENA_ORG['district_full']).'</td></tr>'
        .'<tr><th>ชื่อผู้รับเงิน</th><td>'.pdf_h(pval($project, 'lecturer_name', '')).'</td></tr>'
        .'<tr><th>เลขประจำตัวประชาชน</th><td>'.pdf_h(pval($project, 'lecturer_id_card', '')).'</td></tr>'
        .'<tr><th>ที่อยู่</th><td>'.pdf_h(pval($project, 'lecturer_address', '')).'</td></tr>'
        .'<tr><th>โทรศัพท์</th><td>'.pdf_h(pval($project, 'lecturer_phone', '')).'</td></tr>'
        .'<tr><th>ธนาคาร</th><td>........................................................................................................................</td></tr>'
        .'<tr><th>สาขา</th><td>........................................................................................................................</td></tr>'
        .'<tr><th>ชื่อบัญชี</th><td>........................................................................................................................</td></tr>'
        .'<tr><th>เลขที่บัญชี</th><td class="account-boxes">_ _ _ - _ - _ _ _ _ _ - _</td></tr></table>'
        .'<p><span class="checkbox"></span> ขอรับเงินโอนเข้าบัญชีธนาคารตามข้อมูลข้างต้น<br>'
        .'<span class="checkbox"></span> ยินยอมให้หักค่าธรรมเนียมธนาคาร (ถ้ามี)</p>'
        .'<div class="signature large-signature">ลงชื่อ ........................................................ ผู้มีสิทธิรับเงิน<br>'
        .'( '.pdf_h(pval($project, 'lecturer_name', '........................................................')).' )<br>'
        .'วันที่ ........................................................</div>'
        .'<p class="note"><b>หมายเหตุ</b> โปรดแนบสำเนาหน้าสมุดบัญชีธนาคารที่รับรองสำเนาถูกต้อง</p></section>';

    return pdf_native_receipt($project).'<pagebreak />'.$evidence.'<pagebreak />'.$ktb;
}

function pdf_native_follow_up(array $project, array $students): string
{
    $rows = $students !== [] ? $students : array_fill(0, 10, []);
    $html = '<section class="official-form">'.pdf_form_code('พต. 20')
        .'<div class="title">แบบติดตามผู้ผ่านการฝึกอบรม</div>'
        .'<div class="subtitle">การจัดการเรียนรู้เพื่อการพัฒนาตนเอง</div>'
        .'<p class="center">หลักสูตร '.pdf_h(pval($project, 'course_name')).' จำนวน '.pdf_h(pval($project, 'course_hours')).' ชั่วโมง<br>'
        .'ดำเนินการระหว่างวันที่ '.pdf_h(pdf_thai_date($project['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($project['end_date'] ?? null)).'<br>'
        .'สถานที่ '.pdf_h(pval($project, 'place')).' '.pdf_h(pval($project, 'address')).'</p>'
        .'<p><b>คำชี้แจง</b> ให้ผู้ติดตามบันทึกผลการนำความรู้ไปใช้หลังจบหลักสูตรตามสภาพจริง</p>'
        .'<table class="grid follow-table"><thead><tr><th>ที่</th><th>ชื่อ - สกุล</th><th>การนำความรู้ไปใช้</th><th>ผลที่เกิดขึ้น</th><th>รายได้/ลดรายจ่ายต่อเดือน</th><th>ปัญหาและข้อเสนอแนะ</th></tr></thead><tbody>';

    foreach ($rows as $index => $student) {
        $html .= '<tr><td class="center">'.($index + 1).'</td><td>'.pdf_h($student ? full_name($student) : '').'</td>'
            .'<td><span class="checkbox"></span> สร้างอาชีพ<br><span class="checkbox"></span> เพิ่มรายได้<br><span class="checkbox"></span> ลดรายจ่าย<br><span class="checkbox"></span> อื่น ๆ</td>'
            .'<td></td><td></td><td></td></tr>';
    }

    return $html.'</tbody></table><table class="two-signatures"><tr><td>ลงชื่อ ........................................................ '.pdf_h(SENA_ORG['follow_up_position']).'<br>'
        .'( '.pdf_h(SENA_ORG['follow_up_name']).' )</td><td>ลงชื่อ ........................................................ ผู้รับผิดชอบงาน<br>'
        .'( '.pdf_h(SENA_ORG['responsible_name']).' )</td></tr></table></section>';
}

function pdf_native_announcement(array $project): string
{
    return '<section class="announcement">'.pdf_form_code('พต. 21')
        .'<div class="announcement-garuda"><img width="98" src="'.pdf_h((string) realpath(pdf_asset_path('images/garuda.png'))).'"></div>'
        .'<div class="title">ประกาศ'.pdf_h(SENA_ORG['district_full']).'</div>'
        .'<div class="subtitle">เรื่อง การจัดการเรียนรู้เพื่อการพัฒนาตนเอง</div>'
        .'<div class="announcement-rule"></div>'
        .'<p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['district_full']).' กำหนดจัดการเรียนรู้เพื่อการพัฒนาตนเอง รูปแบบ '
        .pdf_h(pval($project, 'format_type')).' หลักสูตร '.pdf_h(pval($project, 'course_name')).' จำนวน '
        .pdf_h(pval($project, 'course_hours')).' ชั่วโมง ระหว่างวันที่ '.pdf_h(pdf_thai_date($project['start_date'] ?? null)).' ถึง '
        .pdf_h(pdf_thai_date($project['end_date'] ?? null)).' ณ '.pdf_h(pval($project, 'place')).' '.pdf_h(pval($project, 'address')).'</p>'
        .'<p class="thai-distributed">จึงประกาศให้ประชาชนผู้สนใจทราบและสมัครเข้าร่วมกิจกรรมได้ที่ '.pdf_h(SENA_ORG['district_full']).' '
        .'โทรศัพท์ '.pdf_h(SENA_ORG['phone']).' ภายในวันและเวลาราชการ</p>'
        .'<p class="center announcement-date">ประกาศ ณ วันที่ '.pdf_h(pdf_thai_date(date('Y-m-d'))).'</p>'
        .pdf_signature_block().'</section>';
}

function pdf_native_mou(array $project): string
{
    $partyOne = SENA_ORG['district_full'];
    $partyTwo = pval($project, 'place', 'หน่วยงานภาคีเครือข่าย');

    $first = '<section class="mou">'.pdf_form_code('พต. 22')
        .'<div class="mou-garuda"><img width="98" src="'.pdf_h((string) realpath(pdf_asset_path('images/garuda.png'))).'"></div>'
        .'<div class="title">ข้อตกลงความร่วมมือ</div>'
        .'<div class="subtitle">การจัดการเรียนรู้เพื่อการพัฒนาตนเอง</div>'
        .'<p class="center">ระหว่าง<br><b>'.pdf_h($partyOne).'</b><br>กับ<br><b>'.pdf_h($partyTwo).'</b></p>'
        .'<p class="thai-distributed">ข้อตกลงฉบับนี้จัดทำขึ้นเพื่อประสานความร่วมมือในการจัดกิจกรรม หลักสูตร '
        .pdf_h(pval($project, 'course_name')).' จำนวน '.pdf_h(pval($project, 'course_hours')).' ชั่วโมง ให้ประชาชนได้รับความรู้ '
        .'ทักษะ และสามารถนำไปใช้พัฒนาตนเองและคุณภาพชีวิต</p>'
        .'<p class="section">ข้อ ๑ วัตถุประสงค์</p><ol><li>ส่งเสริมการเรียนรู้ที่สอดคล้องกับความต้องการของประชาชน</li>'
        .'<li>ใช้ทรัพยากร บุคลากร สถานที่ และองค์ความรู้ร่วมกันอย่างเหมาะสม</li>'
        .'<li>ติดตามผลและพัฒนาคุณภาพการจัดกิจกรรมอย่างต่อเนื่อง</li></ol>'
        .'<p class="section">ข้อ ๒ ขอบเขตความร่วมมือ</p><p class="thai-distributed">ทั้งสองฝ่ายร่วมกันวางแผน ประชาสัมพันธ์ '
        .'จัดกิจกรรม สนับสนุนทรัพยากรตามความพร้อม และประเมินผลการดำเนินงาน โดยยึดประโยชน์ของผู้เรียนเป็นสำคัญ</p>'
        .'<p class="section">ข้อ ๓ ระยะเวลา</p><p class="thai-distributed">ข้อตกลงมีผลตั้งแต่วันที่ลงนามเป็นต้นไป '
        .'การเปลี่ยนแปลงหรือยกเลิกให้จัดทำเป็นลายลักษณ์อักษรโดยความเห็นชอบของทั้งสองฝ่าย</p></section>';

    $second = '<section class="mou"><div class="title">หน้าที่และความรับผิดชอบ</div>'
        .'<table class="grid responsibility-table"><tr><th>'.pdf_h($partyOne).'</th><th>'.pdf_h($partyTwo).'</th></tr>'
        .'<tr><td><ol><li>จัดทำหลักสูตรและแผนการเรียนรู้</li><li>ประสานวิทยากรและผู้เรียน</li><li>กำกับ ติดตาม และรายงานผล</li></ol></td>'
        .'<td><ol><li>สนับสนุนสถานที่หรือทรัพยากรตามความพร้อม</li><li>ร่วมประชาสัมพันธ์และประสานกลุ่มเป้าหมาย</li><li>ร่วมติดตามและให้ข้อเสนอแนะ</li></ol></td></tr></table>'
        .'<p class="thai-distributed">ข้อตกลงฉบับนี้จัดทำขึ้นจำนวน ๒ ฉบับ มีข้อความถูกต้องตรงกัน ทั้งสองฝ่ายได้อ่านและเข้าใจข้อความโดยตลอดแล้ว '
        .'จึงลงลายมือชื่อไว้เป็นหลักฐาน และเก็บรักษาไว้ฝ่ายละ ๑ ฉบับ</p>'
        .'<table class="two-signatures mou-signatures"><tr><td>ลงชื่อ ........................................................ ฝ่ายที่หนึ่ง<br>'
        .'( '.pdf_h(SENA_ORG['director_name']).' )<br>'.pdf_h(SENA_ORG['director_position']).'</td>'
        .'<td>ลงชื่อ ........................................................ ฝ่ายที่สอง<br>( ........................................................ )<br>'
        .'ตำแหน่ง ........................................................</td></tr><tr><td>ลงชื่อ ........................................................ พยาน</td>'
        .'<td>ลงชื่อ ........................................................ พยาน</td></tr></table></section>';

    return $first.'<pagebreak />'.$second;
}

function pdf_native_css(): string
{
    return <<<'CSS'
body { font-family: thsarabunnew135zws; font-size: 16pt; line-height: 1.05; letter-spacing: normal; word-spacing: normal; color: #000; }
h1, h2, h3, p { margin: 0; padding: 0; }
p { margin-bottom: 1.1mm; orphans: 2; widows: 2; }
ol { margin: 1mm 0 2mm 10mm; padding-left: 5mm; }
li { margin-bottom: 1mm; }
.official-form { display: block; }
.center { text-align: center; }
.right { text-align: right; }
.bold, b, strong { font-weight: bold; }
.title { text-align: center; font-weight: bold; font-size: 20pt; line-height: 1.05; margin: 2mm 0; }
.subtitle { text-align: center; font-weight: bold; font-size: 17pt; line-height: 1.05; margin-bottom: 2mm; }
.section { font-weight: bold; margin-top: 3mm; margin-bottom: 1mm; }
.body { text-align: left; line-height: 1.08; letter-spacing: normal; word-spacing: normal; }
.letter-body { text-align: left; line-height: 1.05; letter-spacing: normal; word-spacing: normal; }
.letter-body .thai-distributed { text-indent: 25mm; margin-bottom: 1.8mm; }
.thai-distributed {
    text-align: justify;
    direction: ltr;
    line-height: 1.05;
    text-indent: 18mm;
    letter-spacing: normal;
    word-spacing: normal;
    white-space: normal;
    margin: 0 0 1.1mm;
}
.form-code-row { width: 100%; border-collapse: collapse; margin: 0 0 2mm; }
.form-code-row td { border: 0; }
.form-code-row td:first-child { width: 70%; }
.form-code { width: 38mm; border: .3mm solid #111 !important; padding: 2mm; text-align: center; font-weight: bold; font-size: 15pt; }
.copy-label { font-weight: bold; margin-bottom: 1mm; }
.line, .line-long { display: inline-block; border-bottom: .22mm dotted #222; min-width: 24mm; padding: 0 .8mm; text-align: center; line-height: .95; }
.line-long { min-width: 72mm; }
.line-long.wide, .wide { min-width: 125mm; }
.checkbox { display: inline-block; width: 3.5mm; height: 3.5mm; border: .25mm solid #111; margin: 0 .8mm 0 1.5mm; vertical-align: middle; }
.blank-lines { line-height: 1.35; }
.blank-line { height: 7mm; border-bottom: .2mm dotted #333; }
.note { font-size: 13pt; margin-top: 7mm; }
.document-date { margin-top: 8mm; }
.org-footer { margin-top: 17mm; line-height: 1.04; font-size: 16pt; }
.memo-head { width: 100%; border-collapse: collapse; margin-bottom: 1mm; }
.memo-head td { width: 33.33%; border: 0; vertical-align: middle; }
.memo-garuda { text-align: left; }
.memo-garuda img { width: 20mm; }
.memo-title { text-align: center; font-weight: bold; font-size: 29pt; white-space: nowrap; }
.memo-code-cell, .letter-code-cell { text-align: right !important; vertical-align: top !important; }
.form-code-inline { display: inline-block; width: 34mm; border: .3mm solid #111; padding: 1.8mm 1mm; text-align: center; font-weight: bold; font-size: 15pt; line-height: 1; }
.memo-row { font-size: 17pt; line-height: 1.04; margin-bottom: .8mm; }
.memo-label { font-weight: bold; }
.rule { border-bottom: .35mm solid #111; height: 1mm; margin-bottom: 2mm; }
.letter-heading { width: 100%; height: 34mm; border-collapse: collapse; }
.letter-heading td { border: 0; text-align: center; vertical-align: top; }
.letter-heading td:first-child { width: 30.208%; }
.letter-heading td:nth-child(2) { width: 33.333%; }
.letter-heading td:last-child { width: 36.459%; text-align: right; }
.letter-heading img { width: 34mm; }
.letter-top { width: 100%; border-collapse: collapse; margin-top: -8mm; }
.letter-top td { width: 50%; border: 0; vertical-align: top; line-height: 1.05; }
.letter-date { width: 85mm; margin: 2mm 0 3.5mm auto; text-align: left; }
.letter-field { margin: 0 0 1.8mm; }
.attachments { width: 100%; border-collapse: collapse; margin: 0 0 3mm; }
.attachments td { border: 0; vertical-align: top; }
.signature { width: 72mm; margin: 8mm 8mm 0 auto; text-align: center; line-height: 1.12; }
.signature-space { height: 11mm; }
.external-signature { width: 85mm; margin: 7mm 0 0 auto; text-align: left; line-height: 1.05; page-break-inside: avoid; }
.external-signature-space { height: 17mm; }
/* Closing starts on the garuda centreline; identity lines share its visual centre. */
.external-signature-identity { width: 80mm; margin-left: -29mm; text-align: center; }
.external-signature-position { font-size: 16pt; }
.large-signature { margin-top: 18mm; }
.two-signatures { width: 100%; border-collapse: collapse; margin-top: 10mm; page-break-inside: avoid; }
.two-signatures td { width: 50%; border: 0; text-align: center; vertical-align: top; padding: 2mm; }
.memo-budget { width: 100%; border-collapse: collapse; margin: 1mm 0 1mm 10mm; font-size: 15pt; }
.memo-budget td { width: 50%; border: 0; padding: .35mm 7mm .35mm 0; vertical-align: top; }
.signature-row td { padding-top: 12mm; }
.approval-box { border: .3mm solid #222; padding: 5mm; margin-top: 16mm; page-break-inside: avoid; }
table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 14pt; line-height: 1.06; page-break-inside: auto; }
table.grid th, table.grid td { border: .25mm solid #222; padding: 1.3mm 1mm; vertical-align: middle; overflow-wrap: break-word; }
table.grid th { text-align: center; font-weight: bold; background: #fff; }
table.grid thead { display: table-header-group; }
table.grid tr { page-break-inside: avoid; }
table.grid.small { font-size: 12.5pt; }
table.grid.tiny { font-size: 11pt; }
.money-column { width: 35mm; }
.receipt-table tbody tr:first-child { height: 55mm; }
.follow-table { font-size: 11.5pt !important; }
.follow-table th:nth-child(1) { width: 7mm; }
.follow-table th:nth-child(2) { width: 32mm; }
.follow-table th:nth-child(3) { width: 29mm; }
.follow-table th:nth-child(4) { width: 30mm; }
.follow-table th:nth-child(5) { width: 28mm; }
.follow-table tr { height: 18mm; }
.form-table { width: 100%; border-collapse: collapse; margin-top: 5mm; }
.form-table th, .form-table td { border: .25mm solid #222; padding: 2mm; vertical-align: top; }
.form-table th { width: 42mm; text-align: left; }
.account-boxes { font-size: 20pt; letter-spacing: 1.2mm; }
.announcement-garuda, .mou-garuda { text-align: center; height: 27mm; }
.announcement-garuda img, .mou-garuda img { width: 22mm; }
.announcement-rule { width: 50mm; margin: 3mm auto 6mm; border-top: .3mm solid #111; }
.announcement-date { margin-top: 8mm; }
.responsibility-table { margin-top: 6mm; }
.responsibility-table td { height: 70mm; vertical-align: top !important; }
.mou-signatures td { padding-top: 15mm; }
.photo-grid { width: 100%; border-collapse: collapse; margin-top: 3mm; }
.photo-grid td { width: 50%; height: 88mm; padding: 3mm; text-align: center; vertical-align: middle; border: 0; }
.photo-grid img { max-width: 72mm; max-height: 72mm; }
.photo-placeholder-table { width: 72mm; height: 67mm; margin: 0 auto; border-collapse: collapse; }
.photo-placeholder-table td { width: 72mm; height: 67mm; padding: 0; border: .3mm dashed #777; color: #555; text-align: center; vertical-align: middle; }
.certificate { text-align: center; }
.cert-frame { border: 1mm double #111; min-height: 170mm; padding: 10mm; }
.cert-garuda { width: 23mm; }
.cert-org { font-size: 25pt; font-weight: bold; margin-top: 4mm; }
.cert-person { font-size: 30pt; font-weight: bold; color: #000; margin: 7mm 0 3mm; }
.cert-text { font-size: 21pt; line-height: 1.3; }
.open-memo { font-family: thsarabunnew135zws; font-size: 16pt; line-height: 1.02; }
.open-top { width: 100%; border-collapse: collapse; height: 20mm; }
.open-top td { width: 33.33%; border: 0; vertical-align: top; }
.open-top img { width: 18mm; }
.open-title { margin: -11mm 0 2mm; text-align: center; font-size: 25pt; }
.open-row { margin: 0 0 1mm; white-space: nowrap; }
.open-label { font-weight: bold; }
.open-fill { display: inline-block; text-align: center; border-bottom: .22mm dotted #222; padding: 0 .5mm; line-height: .93; }
.open-rule { border-bottom: .3mm solid #222; margin: .7mm 0 1.8mm; }
.open-body { line-height: 1.08; letter-spacing: normal; word-spacing: normal; }
.open-body p { margin: 0 0 1mm; text-align: left; }
.open-body p.thai-distributed { text-align: justify; }
.open-budget { width: 100%; border-collapse: collapse; margin: 0 0 .5mm 12mm; font-size: 14pt; }
.open-budget td { width: 50%; border: 0; padding: .5mm 5mm .5mm 0; }
.open-sign { width: 72mm; margin: 14mm 8mm 0 auto; text-align: center; }
.open-sign-space { height: 6mm; }
.invite-page { font-family: thsarabunnew135zws; font-size: 16pt; line-height: 1.05; }
.invite-top { width: 100%; border-collapse: collapse; margin: 0; height: 34mm; }
.invite-top td { width: 33.33%; border: 0; vertical-align: top; }
.invite-top img { width: 25mm; }
.invite-code { width: 36mm; margin-left: auto; border-collapse: collapse; }
.invite-code td { border: .35mm solid #111; padding: 2.5mm 1mm; text-align: center; font-weight: bold; }
.invite-address { width: 100%; border-collapse: collapse; margin-top: -5mm; }
.invite-address td { width: 50%; border: 0; vertical-align: top; }
.invite-address td:last-child { padding-left: 18mm; }
.invite-meta { margin: 0 0 2.2mm; }
.invite-body { margin-top: 2.5mm; line-height: 1.08; text-align: left; }
.invite-body p { margin: 0 0 1.2mm; }
.invite-body p.thai-distributed { text-align: justify; }
.invite-contact { margin-top: 24mm; line-height: 1.08; }
.reply-page { font-family: thsarabunnew135zws; font-size: 14.5pt; line-height: 1.03; }
.reply-title { text-align: center; font-weight: bold; font-size: 18pt; margin: 0 0 1mm; }
.reply-center { text-align: center; margin: 0 0 1mm; }
.reply-rule { border-bottom: .3mm solid #222; margin: 3mm 0 5mm; }
.reply-row { margin: 0 0 1.2mm; }
.reply-section { font-weight: bold; margin: 2mm 0 1mm; }
.reply-sign { width: 80mm; margin: 10mm 5mm 0 auto; text-align: left; line-height: 1.22; }
.reply-note { border-top: .3mm solid #222; margin-top: 6mm; padding-top: 5mm; line-height: 1.16; }
CSS;
}
