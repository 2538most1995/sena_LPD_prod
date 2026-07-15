<?php

declare(strict_types=1);

require_once __DIR__.'/pdf_helpers.php';

function pdf_template(string $key, array $p, array $students, bool $blank = false): string
{
    return match ($key) {
        'pt_1' => pdf_pt1(), 'pt_2' => pdf_pt2($p), 'pt_3' => pdf_pt3($p),
        'pt_4' => pdf_pt4($p), 'pt_5' => pdf_pt5($p), 'pt_6' => pdf_pt6($p, $students),
        'pt_7' => pdf_pt7($p, $students), 'pt_8' => pdf_pt8($p, $students),
        'pt_9_1' => pdf_pt91($p, $students), 'pt_9_2' => pdf_pt92($p),
        'pt_10_1' => pdf_pt10($p, $students, false), 'pt_10_2' => pdf_pt10($p, $students, true),
        'pt_11' => pdf_pt11($p, $students), 'pt_12' => pdf_pt12($p),
        'pt_13' => pdf_pt13($p, $students), 'pt_14' => pdf_pt14($p, $students),
        'pt_15' => pdf_pt15($p, $students), 'pt_16' => pdf_pt16($p, $students),
        'pt_17' => pdf_pt17($p), 'pt_18_1' => pdf_pt18($p, false), 'pt_18_2' => pdf_pt18($p, true),
        'pt_23' => pdf_pt23($p), 'pt_24' => pdf_pt24($p, $students),
        'time_blank' => pdf_time_blank($p, $students, false), 'time_blank_two' => pdf_time_blank($p, $students, true),
        'lecturer_time_blank' => pdf_lecturer_time_blank($p),
        'photo_material' => pdf_photo_report($p, 'material'), 'photo_activity' => pdf_photo_report($p, 'activity'),
        'open_group' => pdf_open_group($p, $students),
        'invite_lecturer' => pdf_invite_lecturer($p),
        default => '<h1 class="title">ไม่พบแม่แบบเอกสาร</h1>',
    };
}

/**
 * Full opening-group memorandum used by the "เปิดกลุ่ม" menu.
 * This deliberately follows the compact one-page layout used by the legacy
 * C-Smart form while keeping Sena LPD data and the current PT.7 form identity.
 */
function pdf_open_group(array $p, array $students): string
{
    $g = pdf_gender_counts($students);
    $total = pdf_project_total($p);
    $format = pval($p, 'format_type', 'หลักสูตร 3-9 ชั่วโมง');
    $budget = pdf_budget_rows($p);
    $budgetNames = array_keys($budget);
    $budgetValues = array_values($budget);
    $garuda = pdf_h((string) realpath(pdf_asset_path('images/garuda.png')));

    $line = static fn (string $text, string $width = 'auto'): string => '<span class="open-fill" style="min-width:'.$width.'">'.pdf_h($text).'</span>';

    $html = '<div class="open-memo"><table class="open-top"><tr><td><img width="74" src="'.$garuda.'"></td><td></td><td class="open-code-cell"><table align="right" style="width:42mm;border:.35mm solid #111;border-collapse:collapse"><tr><td style="padding:3mm 2mm;text-align:center;font-weight:bold;font-size:16pt">แบบ พต. 7</td></tr></table></td></tr></table><h1 class="open-title">บันทึกข้อความ</h1>';
    $html .= '<p class="open-row"><span class="open-label">ส่วนราชการ</span> '.$line(SENA_ORG['learning_center'].'  '.SENA_ORG['district_short'], '108mm').' <span class="open-label">โทรศัพท์</span> '.$line(SENA_ORG['phone'], '28mm').'</p>';
    $html .= '<p class="open-row"><span class="open-label">ที่</span> '.pdf_h(SENA_ORG['official_no']).'/'.$line('', '53mm').' <span class="open-label">วันที่</span> '.$line('', '62mm').'</p>';
    $subject = 'ขออนุมัติจัดฝึกอบรม รูปแบบ '.$format.' หลักสูตร '.$p['course_name'].' จำนวน '.$p['course_hours'].' ชม.';
    $html .= '<p class="open-row"><span class="open-label">เรื่อง</span> '.$line($subject, '151mm').'</p><div class="open-rule"></div>';
    $html .= '<p class="open-row"><span class="open-label">เรียน</span> ผู้อำนวยการ'.pdf_h(SENA_ORG['district_full']).'</p>';
    $html .= '<div class="open-body">';
    // Narrative paragraphs must stay as continuous text. Splitting values into
    // fixed-width inline-block spans prevents mPDF from finding natural Thai
    // line-break points and produces large gaps at the right margin.
    $html .= '<p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['learning_center'])
        .' มีความประสงค์ขออนุมัติจัดโครงการ รูปแบบ '.pdf_h($format)
        .' หลักสูตร '.pdf_h((string) $p['course_name']).' จำนวน '.pdf_h((string) $p['course_hours'])
        .' ชั่วโมง ให้แก่ประชาชน สถานที่จัด ณ '.pdf_h((string) $p['place']).' '.pdf_h((string) $p['address'])
        .' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '
        .pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? ''))
        .' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. มีผู้เรียนทั้งหมดจำนวน '.pdf_h((string) $g['all'])
        .' คน ชาย '.pdf_h((string) $g['male']).' คน หญิง '.pdf_h((string) $g['female'])
        .' คน รายชื่อดังแนบ โดยมี '.pdf_h((string) $p['lecturer_name']).' เป็นวิทยากรให้ความรู้</p>';
    $html .= '<p class="thai-distributed">โดยขอใช้เงินงบประมาณปี '.pdf_h((string) $p['fiscal_year'])
        .' แผนงาน : ยุทธศาสตร์พัฒนาคุณภาพการศึกษาและการเรียนรู้ ผลผลิตผู้รับบริการการเรียนรู้เพื่อพัฒนาคุณภาพชีวิต '
        .'กิจกรรมเรียนรู้เพื่อพัฒนาตนเอง งบรายจ่ายอื่น ('.pdf_h($format).') ภายในวงเงิน '.pdf_money($total)
        .' บาท ('.pdf_h(pdf_baht_text($total)).') รายละเอียดดังแนบ นั้น</p>';
    $html .= '<p class="thai-distributed">ในการนี้ ขออนุมัติจัดฝึกอบรมภายใต้หลักสูตรดังกล่าว ตามวัน เวลา และสถานที่ที่กำหนด โดยมีรายละเอียดค่าใช้จ่าย ดังนี้</p>';
    $html .= '<p style="margin-left:18mm">1. อนุญาตให้เปิดสอนหลักสูตร '.$line((string) $p['course_name'], '70mm').' จำนวน '.$line((string) $p['course_hours'], '12mm').' ชั่วโมง ในระหว่างวันที่ '.$line(pdf_thai_date($p['start_date'] ?? null), '42mm').' ถึง '.$line(pdf_thai_date($p['end_date'] ?? null), '42mm').'</p>';
    $html .= '<p style="margin-left:18mm">โดยมีค่าใช้จ่ายในการเปิดสอนหลักสูตร ดังนี้</p><table class="open-budget">';
    for ($i = 0; $i < 4; $i++) {
        $html .= '<tr>';
        foreach ([$i * 2, $i * 2 + 1] as $j) {
            $html .= '<td>'.pdf_h($budgetNames[$j]).' '.$line(pdf_money($budgetValues[$j]), '27mm').' บาท</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table><p style="margin-left:18mm">2. ลงนามในคำสั่งแต่งตั้งวิทยากร/หนังสือเชิญวิทยากร</p>';
    $html .= '<p style="margin-left:18mm;margin-top:3mm">จึงเรียนมาเพื่อโปรดอนุมัติ</p></div>';
    $html .= '<div class="open-sign"><div class="open-sign-space"></div>ลงชื่อ........................................................<br>( '.pdf_h(SENA_ORG['responsible_name']).' )<br>'.pdf_h(SENA_ORG['responsible_position']).'</div></div>';

    return $html;
}

/** Two-page external invitation and lecturer response form. */
function pdf_invite_lecturer(array $p): string
{
    $garuda = pdf_h((string) realpath(pdf_asset_path('images/garuda.png')));
    // mPDF collapses empty inline-block spans in some Thai font/layout cases.
    // Literal dotted leaders keep every blank field visible and printable.
    $reply = static function (string $text = '', string $width = '45mm'): string {
        $target = max(8, (int) round((float) $width * 0.72));
        $dots = str_repeat('.', max(4, $target - mb_strlen($text, 'UTF-8')));

        return '<span>'.pdf_h($text).$dots.'</span>';
    };

    $project = pval($p, 'title', '');
    $course = pval($p, 'course_name', '');
    $dates = pdf_thai_date($p['start_date'] ?? null).' ถึง '.pdf_thai_date($p['end_date'] ?? null);
    $place = trim(pval($p, 'place', '').' '.pval($p, 'address', ''));
    $lecturer = pval($p, 'lecturer_name', '');

    $h = '<div class="invite-page">';
    $h .= '<table class="invite-top"><tr><td></td><td style="text-align:center"><img width="98" src="'.$garuda.'"></td><td><table class="invite-code"><tr><td>แบบ พต. 4</td></tr></table></td></tr></table>';
    $h .= '<table class="invite-address"><tr><td>ที่ '.pdf_h(SENA_ORG['official_no']).'/........................</td><td>'.pdf_h(SENA_ORG['district_full']).'<br>'.pdf_h(SENA_ORG['address']).'</td></tr></table>';
    $h .= '<p class="center" style="margin:4mm 0 3mm">........................................................</p>';
    $h .= '<p class="invite-meta"><b>เรื่อง</b>&nbsp;&nbsp;&nbsp; ขอเชิญเป็นวิทยากร</p>';
    $h .= '<p class="invite-meta"><b>เรียน</b>&nbsp;&nbsp;&nbsp; '.pdf_h($lecturer).'</p>';
    $h .= '<table style="width:100%;border-collapse:collapse;margin-bottom:3mm"><tr><td style="width:30mm;border:0"><b>สิ่งที่ส่งมาด้วย</b></td><td style="border:0">แบบตอบรับการเป็นวิทยากร</td><td style="width:35mm;border:0;text-align:right">จำนวน 1 ฉบับ</td></tr></table>';
    $h .= '<div class="invite-body">';
    $h .= '<p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['district_full']).' กำหนดจัดโครงการ '.pdf_h($project).' ให้แก่ประชาชน ณ '.pdf_h($place).' ในระหว่างวันที่ '.pdf_h($dates).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. จำนวนผู้เรียนตามบัญชีรายชื่อ โดยมีวัตถุประสงค์ '.pdf_h(pval($p, 'objective', '')).' รายละเอียดตามสิ่งที่ส่งมาด้วยพร้อมหนังสือนี้</p>';
    $h .= '<p class="thai-distributed">'.pdf_h(SENA_ORG['district_full']).' พิจารณาแล้วเห็นว่า ท่านเป็นผู้ที่มีความรู้ ความสามารถ และประสบการณ์ที่จะให้ความรู้แก่ผู้เข้ารับการฝึกอบรมได้เป็นอย่างดี จึงขอเชิญเป็นวิทยากรบรรยายให้ความรู้ตามวันและเวลาดังกล่าว</p>';
    $h .= '<p class="thai-distributed">จึงเรียนมาเพื่อโปรดพิจารณารับเชิญเป็นวิทยากร และขอขอบคุณมา ณ โอกาสนี้</p>';
    $h .= '<p style="text-align:center;margin-top:6mm">ขอแสดงความนับถือ</p>';
    $h .= '</div><div class="invite-contact">กลุ่มส่งเสริมการเรียนรู้เพื่อพัฒนาตนเอง<br>โทร. '.pdf_h(SENA_ORG['phone']).'<br>โทรสาร '.pdf_h(SENA_ORG['fax']).'</div></div>';

    $h .= '<pagebreak><div class="reply-page">';
    $h .= '<p class="reply-title">แบบตอบรับการเป็นวิทยากร</p>';
    $h .= '<p class="reply-center"><b>โครงการ/หลักสูตร</b> '.$reply($project.' / '.$course, '105mm').'</p>';
    $h .= '<p class="reply-center"><b>ในระหว่างวันที่</b> '.$reply($dates, '75mm').'</p>';
    $h .= '<p class="reply-center"><b>ณ</b> '.$reply($place, '120mm').'</p><div class="reply-rule"></div>';
    $h .= '<p class="reply-row">ชื่อ'.$reply('', '74mm').'นามสกุล'.$reply('', '74mm').'</p>';
    $h .= '<p class="reply-row">อาชีพ'.$reply('', '72mm').'หน่วยงาน'.$reply('', '72mm').'</p>';
    $h .= '<p class="reply-row">ที่อยู่ปัจจุบัน บ้านเลขที่'.$reply('', '27mm').'หมู่ที่'.$reply('', '22mm').'ตำบล/แขวง'.$reply('', '31mm').'อำเภอ/เขต'.$reply('', '31mm').'</p>';
    $h .= '<p class="reply-row">จังหวัด'.$reply('', '42mm').'รหัสไปรษณีย์'.$reply('', '37mm').'โทรศัพท์'.$reply('', '45mm').'</p>';
    $h .= '<p class="reply-section">ประสบการณ์ ความเชี่ยวชาญ</p>';
    $h .= '<p class="reply-row">วุฒิการศึกษา'.$reply('', '72mm').'ความสามารถพิเศษ'.$reply('', '58mm').'</p>';
    $h .= '<p class="reply-row">ปัจจุบันประกอบอาชีพ'.$reply('', '65mm').'สถานที่ทำงาน'.$reply('', '55mm').'</p>';
    $h .= '<p class="reply-row">ตำบล'.$reply('', '35mm').'อำเภอ'.$reply('', '35mm').'จังหวัด'.$reply('', '35mm').'รหัสไปรษณีย์'.$reply('', '35mm').'</p>';
    $h .= '<p class="reply-row">ประสบการณ์งานการศึกษาต่อเนื่อง เคยสอนหลักสูตร'.$reply($course, '87mm').'</p>';
    $h .= '<p class="reply-row">ระยะเวลา'.$reply('', '24mm').'ปี สถานที่สอน'.$reply('', '55mm').'อำเภอ'.$reply('', '35mm').'จังหวัด'.$reply('', '35mm').'</p>';
    $h .= '<p style="margin:5mm 0 1mm 28mm">( &nbsp;&nbsp; ) มาเป็นวิทยากรได้</p><p style="margin:0 0 1mm 28mm">( &nbsp;&nbsp; ) ไม่อาจมาเป็นวิทยากรได้ เนื่องจาก'.$reply('', '90mm').'</p><p style="margin:0 0 5mm 28mm">แนะนำ'.$reply('', '82mm').'เป็นวิทยากรแทน</p>';
    $h .= '<p class="reply-section">รายละเอียดอาหาร</p><p style="margin-left:28mm">( &nbsp;&nbsp; ) อาหารธรรมดา&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( &nbsp;&nbsp; ) อาหารอิสลาม</p>';
    $h .= '<div class="reply-sign">ลงชื่อ'.$reply('', '54mm').'ผู้แจ้งข้อมูล<br>('.$reply('', '66mm').')<br>ตำแหน่ง'.$reply('', '58mm').'<br>โทรศัพท์มือถือ'.$reply('', '50mm').'</div>';
    $h .= '<div class="reply-note"><b>หมายเหตุ</b><br>1. กรุณาส่งแบบตอบรับการเป็นวิทยากรโครงการ/หลักสูตรฯ ไปยัง'.$reply('', '93mm').'<br>&nbsp;&nbsp;&nbsp;ภายในวันที่'.$reply('', '45mm').'ทาง'.$reply('', '65mm').'<br>2. ทางโครงการ รับผิดชอบค่าใช้จ่ายต่าง ๆ จากโครงการ'.$reply('', '88mm').'<br>3. หากมีข้อสงสัยประการใด ขอให้ติดต่อสอบถามรายละเอียดเพิ่มเติมได้ที่'.$reply('', '70mm').'<br>&nbsp;&nbsp;&nbsp;เบอร์โทรศัพท์'.$reply(SENA_ORG['phone'], '55mm').'</div></div>';

    return $h;
}

function pval(array $p, string $key, string $fallback = '........................................................'): string
{
    $v = trim((string) ($p[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
}

function pdf_pt1(): string
{
    return pdf_form_code('พต. 1').'<div class="title">แบบสำรวจความต้องการการจัดการเรียนรู้เพื่อการพัฒนาตนเอง</div><div class="subtitle">สำหรับประชาชน</div>'.
    '<p class="section">ส่วนที่ 1 ข้อมูลส่วนตัว</p><div class="blank-lines">1. ชื่อ (นาย/นาง/นางสาว) <span class="line-long">&nbsp;</span><br>2. อาชีพปัจจุบัน <span class="checkbox"></span> ไม่มี <span class="checkbox"></span> มี ระบุอาชีพ <span class="line-long">&nbsp;</span><br>3. วุฒิการศึกษา <span class="checkbox"></span> ต่ำกว่าประถมศึกษา <span class="checkbox"></span> ประถมศึกษา <span class="checkbox"></span> มัธยมศึกษาตอนต้น <span class="checkbox"></span> มัธยมศึกษาตอนปลาย<br><span class="checkbox"></span> อนุปริญญา <span class="checkbox"></span> ปริญญาตรี <span class="checkbox"></span> สูงกว่าปริญญาตรี<br>4. วุฒิบัตร/เกียรติบัตร/ประกาศนียบัตร <span class="line-long">&nbsp;</span><br>5. ที่อยู่ปัจจุบัน <span class="line-long" style="min-width:125mm">&nbsp;</span><br>เบอร์โทรศัพท์ <span class="line-long">&nbsp;</span></div>'.
    '<p class="section">ส่วนที่ 2 ความต้องการในการเรียนรู้</p><p class="bold">2.1 ด้านการประกอบและพัฒนาอาชีพ</p><p><span class="checkbox"></span> การทำกรอบรูป <span class="checkbox"></span> เพ้นท์เล็บ <span class="checkbox"></span> ยูทูบเบอร์ <span class="checkbox"></span> ช่างไฟฟ้าเบื้องต้น <span class="checkbox"></span> ค้าขายออนไลน์ <span class="checkbox"></span> การทำปุ๋ยหมัก<br><span class="checkbox"></span> อื่น ๆ (โปรดระบุ) <span class="line-long">&nbsp;</span> จำนวนชั่วโมง <span class="checkbox"></span> 3-9 ชั่วโมง <span class="checkbox"></span> 10 ชั่วโมงขึ้นไป</p>'.
    '<p class="bold">2.2 ด้านทักษะชีวิต</p><p><span class="checkbox"></span> สุขภาวะ <span class="checkbox"></span> ความปลอดภัยในชีวิตและทรัพย์สิน <span class="checkbox"></span> คุณธรรม จริยธรรม ค่านิยมและคุณลักษณะที่พึงประสงค์<br><span class="checkbox"></span> ทรัพยากรธรรมชาติและสิ่งแวดล้อม <span class="checkbox"></span> เทคโนโลยีและนวัตกรรม <span class="checkbox"></span> อื่น ๆ <span class="line">&nbsp;</span></p>'.
    '<p class="bold">2.3 ด้านพัฒนาสังคมและชุมชน</p><p><span class="checkbox"></span> ศาสตร์พระราชา <span class="checkbox"></span> สถาบันหลักของชาติ <span class="checkbox"></span> ความเป็นพลเมือง <span class="checkbox"></span> เศรษฐกิจ สังคม <span class="checkbox"></span> ศิลปวัฒนธรรมและประเพณี</p><p>2.4 วันและเวลาที่สามารถมาเรียน <span class="checkbox"></span> วันจันทร์-ศุกร์ <span class="checkbox"></span> วันเสาร์-อาทิตย์ ระบุช่วงเวลา <span class="line">&nbsp;</span></p>'.
    '<p class="section">ส่วนที่ 3 จุดมุ่งหมายในการเรียนรู้ (เลือกได้มากกว่า 1 ข้อ)</p><p><span class="checkbox"></span> ต้องการมีรายได้ <span class="checkbox"></span> ลดรายจ่าย <span class="checkbox"></span> ต้องการมีอาชีพหรือพัฒนาอาชีพ <span class="checkbox"></span> แก้ปัญหาในการดำรงชีวิต<br><span class="checkbox"></span> มีทักษะ <span class="checkbox"></span> สร้างมูลค่าเพิ่ม <span class="checkbox"></span> ต่อยอดภูมิปัญญาท้องถิ่น <span class="checkbox"></span> พัฒนาอาชีพสู่วิสาหกิจชุมชน<br><span class="checkbox"></span> ต้องการได้รับการพัฒนาความรู้ <span class="checkbox"></span> ใช้เวลาว่างให้เกิดประโยชน์ <span class="checkbox"></span> อื่น ๆ <span class="line-long">&nbsp;</span></p>';
}

function pdf_pt2(array $p): string
{
    $h = pdf_form_code('พต. 2').'<p class="right">เลขที่ ........................</p><div class="title">ใบสมัครผู้เรียนหลักสูตรการจัดการเรียนรู้เพื่อการพัฒนาตนเอง</div><div class="subtitle">'.pdf_h(SENA_ORG['district_full']).'<br>'.pdf_h(SENA_ORG['province_office']).' กรมส่งเสริมการเรียนรู้ กระทรวงศึกษาธิการ</div><p class="center">หลักสูตร/กิจกรรม <span class="line-long">'.pdf_h(pval($p, 'course_name', '')).'</span> จำนวน <span class="line">'.pdf_h(pval($p, 'course_hours', '')).'</span> ชั่วโมง</p>';
    $h .= '<p class="section">1. ข้อมูลส่วนตัว (กรุณากรอกข้อมูลด้วยตัวบรรจง)</p><p>ชื่อ-นามสกุล (นาย/นาง/นางสาว) <span class="line-long" style="min-width:110mm">&nbsp;</span><br>เลขบัตรประจำตัวประชาชน/หนังสือเดินทาง (passport) <span class="line-long">&nbsp;</span><br>เกิดวันที่/เดือน/พ.ศ. <span class="line">&nbsp;</span> อายุ <span class="line">&nbsp;</span> ปี สัญชาติ <span class="line">&nbsp;</span> ศาสนา <span class="line">&nbsp;</span> อาชีพ <span class="line">&nbsp;</span></p><p>ระดับการศึกษา <span class="checkbox"></span> ต่ำกว่าประถมศึกษา <span class="checkbox"></span> ประถมศึกษา <span class="checkbox"></span> มัธยมศึกษาตอนต้น <span class="checkbox"></span> มัธยมศึกษาตอนปลาย <span class="checkbox"></span> อนุปริญญา <span class="checkbox"></span> ปริญญาตรี <span class="checkbox"></span> สูงกว่าปริญญาตรี</p><p>ที่อยู่ปัจจุบัน <span class="line-long" style="min-width:135mm">&nbsp;</span><br>เบอร์โทรศัพท์ <span class="line-long">&nbsp;</span></p>';
    foreach (['2. สนใจเข้าร่วมกิจกรรม เนื่องจาก (เลือกได้มากกว่า 1 ข้อ)' => ['ต้องการมีรายได้', 'ลดรายจ่าย', 'ต้องการมีอาชีพหรือพัฒนาอาชีพ', 'แก้ปัญหาในการดำรงชีวิต', 'มีทักษะ', 'สร้างมูลค่าเพิ่ม', 'ต่อยอดภูมิปัญญาท้องถิ่น', 'พัฒนาอาชีพสู่วิสาหกิจชุมชน', 'ต้องการได้รับการพัฒนาความรู้', 'ใช้เวลาว่างให้เกิดประโยชน์', 'อื่น ๆ'], '3. สถานะของผู้สมัคร' => ['ผู้ไม่มีรายได้', 'ผู้สูงอายุ', 'สมาชิกกองทุนสตรี', 'ผู้พิการ', 'ผู้ถือบัตรสวัสดิการของรัฐ', 'สมาชิกกองทุนหมู่บ้าน', 'อื่น ๆ'], '4. อาชีพของผู้สมัคร' => ['รับราชการ', 'พนักงานรัฐวิสาหกิจ', 'ค้าขาย', 'เกษตรกร', 'รับจ้าง', 'อื่น ๆ'], '5. กลุ่มเป้าหมาย' => ['ผู้นำชุมชน', 'ทหารกองประจำการ', 'แรงงานไทย', 'แรงงานต่างด้าว', 'เกษตรกร', 'อสม.', 'กลุ่มสตรี', 'ผู้ต้องขัง', 'อื่น ๆ']] as $title => $items) {
        $h .= '<p class="section">'.pdf_h($title).'</p><p>';
        foreach ($items as $i) {
            $h .= '<span class="checkbox"></span> '.pdf_h($i).' ';
        }$h .= '</p>';
    }
    $h .= '<p class="section">6. วิธีการเรียนรู้</p><p><span class="checkbox"></span> แบบรวมกลุ่ม <span class="checkbox"></span> แบบรายบุคคล สถานที่/ช่องทางการเรียนรู้ <span class="line-long">&nbsp;</span></p><p class="section">7. ท่านได้รับข่าวสารการรับสมัครจาก <span class="line-long">&nbsp;</span></p><table class="two-signatures"><tr><td>ลงชื่อ ........................................ ผู้สมัคร<br>(........................................................)<br>วันที่ ........./........./.........</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['registrar_position']).'<br>('.pdf_h(SENA_ORG['registrar_name']).')<br>วันที่ ........./........./.........</td></tr></table><p style="margin-top:8mm;font-size:13pt">หมายเหตุ กรณีต่างด้าวต้องมีใบอนุญาตการทำงาน/หนังสือเดินทาง หรือเอกสารอื่นที่ทางราชการออกให้</p>';

    return $h;
}

function pdf_pt3(array $p): string
{
    $h = pdf_form_code('พต. 3').'<div class="title">หลักสูตรฝึกอบรม <span class="line-long">'.pdf_h(pval($p, 'course_name', '')).'</span></div><p class="center">จำนวน <span class="line">'.pdf_h(pval($p, 'course_hours', '')).'</span> ชั่วโมง</p>';
    foreach (['ความเป็นมา', 'หลักการของหลักสูตร', 'เนื้อหา', 'เป้าหมาย/จุดประสงค์การเรียนรู้', 'ระยะเวลา', 'สื่อการเรียนรู้', 'การวัดผลประเมินผล', 'เกณฑ์การจบหลักสูตร'] as $i => $title) {
        $h .= '<p class="section">'.pdf_h($title).'</p>';
        if ($i === 0 && trim((string) ($p['course_description'] ?? '')) !== '') {
            $h .= '<p class="thai-distributed">'.pdf_h($p['course_description']).'</p>';
        } else {
            $h .= '<div class="blank-line"></div><div class="blank-line"></div>';
        }
    }
    $h .= '<pagebreak><div class="title">โครงสร้างหลักสูตร</div><table class="grid"><tr><th rowspan="2" style="width:8mm">ที่</th><th rowspan="2">เรื่อง</th><th rowspan="2">กระบวนการจัดการเรียนรู้</th><th colspan="2">จำนวนชั่วโมง</th></tr><tr><th>ทฤษฎี</th><th>ปฏิบัติ</th></tr>';
    for ($i = 1; $i <= 6; $i++) {
        $h .= '<tr style="height:25mm"><td class="center">'.$i.'</td><td></td><td></td><td></td><td></td></tr>';
    }$h .= '</table>';

    return $h;
}

function pdf_pt4(array $p): string
{
    $subject = 'ขอเชิญเป็นวิทยากร';
    $h = pdf_letter_header($p, $subject, pval($p, 'lecturer_name'), 'พต. 4').'<table class="attachments"><tr><td style="width:32mm" class="bold">สิ่งที่ส่งมาด้วย</td><td>แบบตอบรับการเป็นวิทยากร</td><td class="right">จำนวน 1 ฉบับ</td></tr></table>'.
    '<div class="letter-body"><p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['district_full']).' กำหนดจัดโครงการ '.pdf_h(pval($p, 'title')).' ให้แก่ประชาชน ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. จำนวนผู้เรียนตามบัญชีรายชื่อ โดยมีวัตถุประสงค์ '.pdf_h(pval($p, 'objective')).'</p><p class="thai-distributed">พิจารณาแล้วเห็นว่า ท่านเป็นผู้มีความรู้ ความสามารถ และประสบการณ์ที่จะให้ความรู้แก่ผู้เข้ารับการฝึกอบรมได้เป็นอย่างดี จึงขอเชิญเป็นวิทยากรบรรยายให้ความรู้ตามวันและเวลาดังกล่าว</p><p class="thai-distributed">จึงเรียนมาเพื่อโปรดพิจารณารับเชิญเป็นวิทยากร และขอขอบคุณมา ณ โอกาสนี้</p></div>'.pdf_external_signature_block().pdf_org_footer();
    $h .= '<pagebreak><div class="title">แบบตอบรับการเป็นวิทยากร</div><p class="center">โครงการ/หลักสูตร <span class="line-long">'.pdf_h(pval($p, 'title')).'</span><br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><div class="blank-lines">ชื่อ <span class="line">'.pdf_h(pval($p, 'lecturer_name', '')).'</span> อาชีพ <span class="line">'.pdf_h(pval($p, 'lecturer_career', '')).'</span> หน่วยงาน <span class="line">&nbsp;</span><br>ที่อยู่ปัจจุบัน <span class="line-long">'.pdf_h(pval($p, 'lecturer_address', '')).'</span> โทรศัพท์ <span class="line">'.pdf_h(pval($p, 'lecturer_phone', '')).'</span><br>ประสบการณ์/ความเชี่ยวชาญ <span class="line-long">'.pdf_h(pval($p, 'lecturer_expertise', '')).'</span><br>วุฒิการศึกษา <span class="line-long">'.pdf_h(pval($p, 'lecturer_education', '')).'</span><br><span class="checkbox"></span> มาเป็นวิทยากรได้ &nbsp; <span class="checkbox"></span> ไม่อาจมาเป็นวิทยากรได้ เนื่องจาก <span class="line-long">&nbsp;</span><br>รายละเอียดอาหาร <span class="checkbox"></span> อาหารธรรมดา <span class="checkbox"></span> อาหารอิสลาม</div><div class="signature">ลงชื่อ ........................................ ผู้แจ้งข้อมูล<br>(........................................................)<br>ตำแหน่ง ........................................<br>โทรศัพท์มือถือ ........................................</div>';

    return $h;
}

function pdf_pt5(array $p): string
{
    $h = pdf_form_code('พต. 5').'<div class="title">แบบเขียนโครงการฝึกอบรม</div>';
    $sections = ['1. ชื่อโครงการ' => pval($p, 'title', ''), '2. ความสอดคล้องกับนโยบาย' => 'นโยบายการส่งเสริมการเรียนรู้เพื่อพัฒนาตนเอง', '3. หลักการและเหตุผล' => pval($p, 'course_description', ''), '4. วัตถุประสงค์' => pval($p, 'objective', ''), '5. เป้าหมาย' => 'เชิงปริมาณ ผู้เรียนตามบัญชีรายชื่อ  เชิงคุณภาพ ผู้เรียนมีความรู้และทักษะตามหลักสูตร'];
    foreach ($sections as $t => $v) {
        $h .= '<p class="section">'.pdf_h($t).'</p><p class="thai-distributed">'.pdf_h($v).'</p><div class="blank-line"></div>';
    }
    $h .= '<p class="section">6. วิธีการดำเนินการ</p><table class="grid tiny"><tr><th>กิจกรรมหลัก</th><th>วัตถุประสงค์</th><th>กลุ่มเป้าหมาย</th><th>เป้าหมาย</th><th>พื้นที่ดำเนินการ</th><th>ระยะเวลา</th><th>งบประมาณ</th></tr><tr style="height:28mm"><td>'.pdf_h(pval($p, 'title', '')).'</td><td>'.pdf_h(pval($p, 'objective', '')).'</td><td>ประชาชนทั่วไป</td><td>ตามบัญชีรายชื่อ</td><td>'.pdf_h(pval($p, 'place', '')).'</td><td>'.pdf_h(pdf_thai_date($p['start_date'] ?? null, true)).' - '.pdf_h(pdf_thai_date($p['end_date'] ?? null, true)).'</td><td>'.pdf_money(pdf_project_total($p)).'</td></tr></table><p class="section">7. วงเงินงบประมาณทั้งโครงการ</p>';
    foreach (pdf_budget_rows($p) as $name => $money) {
        $h .= '<p>7.'.pdf_h((string) (array_search($name, array_keys(pdf_budget_rows($p)), true) + 1)).' '.pdf_h($name).' <span class="line">'.pdf_money($money).'</span> บาท</p>';
    }
    $h .= '<pagebreak><p class="section">8. แผนการใช้จ่ายงบประมาณ</p><table class="grid"><tr><th>กิจกรรมหลัก</th><th>ไตรมาสที่ 1</th><th>ไตรมาสที่ 2</th><th>ไตรมาสที่ 3</th><th>ไตรมาสที่ 4</th></tr><tr style="height:25mm"><td>'.pdf_h(pval($p, 'title', '')).'</td><td></td><td></td><td></td><td></td></tr></table>';
    foreach (['9. ผู้รับผิดชอบโครงการ', '10. เครือข่าย', '11. โครงการที่เกี่ยวข้อง', '12. ผลลัพธ์ (Outcome)', '13. ดัชนีตัวชี้วัดผลสำเร็จของโครงการ', '14. การติดตามและประเมินผลโครงการ'] as $t) {
        $h .= '<p class="section">'.pdf_h($t).'</p><div class="blank-line"></div><div class="blank-line"></div>';
    }
    $h .= '<pagebreak>'.pdf_form_code('พต. 5').'<div class="title">กำหนดการจัดอบรมโครงการ '.pdf_h(pval($p, 'title', '')).'</div><p class="center">ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid"><tr><th style="width:35mm">เวลา</th><th>กิจกรรม</th></tr>';
    foreach ([['08.00 - 08.30 น.', 'ลงทะเบียน'], ['09.00 - 10.30 น.', 'ปฐมนิเทศ/บรรยายพิเศษ/ทดสอบความรู้ก่อนการอบรม'], ['10.45 - 12.00 น.', 'เรื่อง ........................................................ โดยวิทยากร ........................................................'], ['12.00 - 13.00 น.', 'พักรับประทานอาหารกลางวัน'], ['13.00 - 14.00 น.', 'เรื่อง ........................................................ โดยวิทยากร ........................................................'], ['14.00 - 14.15 น.', 'รับประทานอาหารว่างพร้อมเครื่องดื่ม'], ['14.15 - 16.30 น.', 'เรื่อง ........................................................ โดยวิทยากร ........................................................']] as $r) {
        $h .= '<tr><td>'.$r[0].'</td><td>'.$r[1].'</td></tr>';
    }$h .= '</table><p style="margin-top:4mm">หมายเหตุ รูปแบบกำหนดการหน่วยจัดการเรียนรู้สามารถปรับได้ตามความเหมาะสม</p>';

    return $h;
}

function pdf_project_memo_body(array $p, array $students, bool $approval): string
{
    $g = pdf_gender_counts($students);
    $total = pdf_project_total($p);
    $h = '<div class="body"><p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['learning_center']).' มีความประสงค์ขออนุมัติ'.($approval ? 'จัดฝึกอบรมภายใต้' : 'โครงการ').' รูปแบบ '.pdf_h(pval($p, 'format_type')).' หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง ให้กับประชาชน สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. มีผู้เรียนทั้งหมด '.$g['all'].' คน ชาย '.$g['male'].' คน หญิง '.$g['female'].' คน รายชื่อดังแนบ โดยมี '.pdf_h(pval($p, 'lecturer_name')).' เป็นวิทยากรให้ความรู้</p><p class="thai-distributed">โดยขอใช้เงินงบประมาณปี '.pdf_h($p['fiscal_year']).' แผนงาน : ยุทธศาสตร์พัฒนาคุณภาพการศึกษาและการเรียนรู้ ผลผลิตผู้รับบริการการเรียนรู้เพื่อพัฒนาคุณภาพชีวิต กิจกรรมเรียนรู้เพื่อพัฒนาตนเอง งบรายจ่ายอื่น ('.pdf_h(pval($p, 'format_type')).') ภายในวงเงิน '.pdf_money($total).' บาท ('.pdf_h(pdf_baht_text($total)).') รายละเอียดดังแนบ</p>';
    if ($approval) {
        $h .= '<p class="thai-distributed">ในการนี้ ขออนุมัติจัดฝึกอบรมภายใต้หลักสูตรดังกล่าว ตามวัน เวลา และสถานที่ที่กำหนด โดยมีรายละเอียดค่าใช้จ่าย ดังนี้</p><p>1. อนุญาตให้เปิดสอนหลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง</p>';
        $budget = array_filter(pdf_budget_rows($p), static fn (float $amount): bool => $amount > 0);
        if ($budget !== []) {
            $items = array_chunk($budget, 2, true);
            $h .= '<table class="memo-budget">';
            foreach ($items as $row) {
                $h .= '<tr>';
                foreach ($row as $n => $m) {
                    $h .= '<td>'.pdf_h($n).' <span class="line">'.pdf_money($m).'</span> บาท</td>';
                }
                if (count($row) === 1) {
                    $h .= '<td></td>';
                }
                $h .= '</tr>';
            }
            $h .= '</table>';
        }
        $h .= '<p>2. ลงนามในคำสั่งแต่งตั้งวิทยากร/หนังสือเชิญวิทยากร</p><p class="thai-distributed">จึงเรียนมาเพื่อโปรดอนุมัติ</p>';
    } else {
        $h .= '<p class="thai-distributed">จึงเรียนมาเพื่อโปรดทราบและพิจารณา</p><p>1. อนุญาตให้เปิดสอนหลักสูตร '.pdf_h(pval($p, 'course_name')).' และจ่ายค่าตอบแทนวิทยากร '.pdf_money($p['lecturer_cost']).' บาท</p><p>2. อนุมัติหลักการจัดซื้อจัดจ้างพัสดุ ภายในวงเงิน '.pdf_money($p['material_cost']).' บาท</p><p>3. ลงนามในคำสั่งแต่งตั้งวิทยากร/หนังสือเชิญวิทยากร</p>';
    }

    return $h.'</div>'.pdf_responsible_signature();
}

function pdf_pt6(array $p, array $s): string
{
    return pdf_memo_header('พต. 6').pdf_memo_meta($p, 'ขออนุมัติโครงการ รูปแบบ '.pval($p, 'format_type').' หลักสูตร '.pval($p, 'course_name').' จำนวน '.pval($p, 'course_hours').' ชม.').pdf_project_memo_body($p, $s, false);
}
function pdf_pt7(array $p, array $s): string
{
    return pdf_memo_header('พต. 7').pdf_memo_meta($p, 'ขออนุมัติจัดฝึกอบรม รูปแบบ '.pval($p, 'format_type').' หลักสูตร '.pval($p, 'course_name').' จำนวน '.pval($p, 'course_hours').' ชม.').pdf_project_memo_body($p, $s, true);
}

function pdf_pt8(array $p, array $students): string
{
    $h = pdf_form_code('พต. 8').'<div class="title">ทะเบียนรายชื่อผู้เรียนหลักสูตรการจัดการศึกษาเพื่อพัฒนาตนเอง</div><div class="subtitle">'.pdf_h(SENA_ORG['district_full']).' จังหวัด'.pdf_h(SENA_ORG['province']).'</div><p class="center">ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid small"><tr><th>ที่</th><th>ชื่อ - สกุล</th><th>เลขบัตรประชาชน</th><th>อายุ</th><th>วุฒิการศึกษา</th><th>อาชีพ</th><th>ที่อยู่ปัจจุบัน</th></tr>';
    $rows = $students ?: array_fill(0, 10, []);
    foreach ($rows as $i => $s) {
        $h .= '<tr style="height:9mm"><td class="center">'.($i + 1).'</td><td>'.pdf_h($s ? full_name($s) : '').'</td><td>'.pdf_h($s['id_card'] ?? '').'</td><td class="center">'.pdf_h(pdf_age($s['birthday'] ?? null)).'</td><td>'.pdf_h($s['education'] ?? '').'</td><td>'.pdf_h($s['career'] ?? '').'</td><td>'.pdf_h($s['address'] ?? '').'</td></tr>';
    }

    return $h.'</table>'.pdf_responsible_signature();
}

function pdf_pt91(array $p, array $students): string
{
    $days = pdf_days($p['start_date'] ?? null, $p['end_date'] ?? null);
    if (! $days) {
        $days = [null];
    }$chunks = array_chunk($days, 2);
    $html = '';
    foreach ($chunks as $ci => $chunk) {
        if ($ci) {
            $html .= '<pagebreak>';
        }$html .= pdf_form_code('พต. 9(1)').'<div class="title">บัญชีลงเวลาของผู้เรียนการจัดการศึกษาเพื่อพัฒนาตนเอง</div><p class="center">หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>วิทยากร '.pdf_h(pval($p, 'lecturer_name')).' จำนวนผู้เรียน '.count($students).' คน<br>ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. - '.pdf_h(pdf_time($p['end_time'] ?? '')).' น.<br>สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid small"><tr><th rowspan="2">ที่</th><th rowspan="2">ชื่อ - สกุล</th>';
        foreach ($chunk as $d) {
            $html .= '<th colspan="3">วันที่ '.pdf_h(pdf_thai_date($d)).'</th>';
        }$html .= '</tr><tr>';
        foreach ($chunk as $d) {
            $html .= '<th>ลายมือชื่อ</th><th>เวลามา</th><th>เวลากลับ</th>';
        }$html .= '</tr>';
        $rows = $students ?: array_fill(0, 10, []);
        foreach ($rows as $i => $s) {
            $html .= '<tr style="height:10mm"><td>'.($i + 1).'</td><td>'.pdf_h($s ? full_name($s) : '').'</td>';
            foreach ($chunk as $d) {
                $html .= '<td></td><td class="center">'.pdf_h(pdf_time($p['start_time'] ?? '')).'</td><td class="center">'.pdf_h(pdf_time($p['end_time'] ?? '')).'</td>';
            }$html .= '</tr>';
        }$html .= '</table><table class="two-signatures"><tr><td>ลงชื่อ ........................................ วิทยากรผู้สอน<br>('.pdf_h(pval($p, 'lecturer_name', '................................')).')</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['responsible_position']).'<br>('.pdf_h(SENA_ORG['responsible_name']).')</td></tr></table>';
    }

    return $html;
}

function pdf_pt92(array $p): string
{
    $h = pdf_form_code('พต. 9(2)').'<div class="title">บัญชีลงเวลาของวิทยากร</div><div class="subtitle">'.pdf_h(SENA_ORG['district_full']).'</div><p class="center">ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid"><tr><th>ที่</th><th>วัน เดือน ปี</th><th>ชื่อ - สกุล</th><th>ลายมือชื่อ</th><th>เวลามา</th><th>ลายมือชื่อ</th><th>เวลากลับ</th></tr>';
    $days = pdf_days($p['start_date'] ?? null, $p['end_date'] ?? null) ?: array_fill(0, 10, null);
    foreach ($days as $i => $d) {
        $h .= '<tr style="height:10mm"><td>'.($i + 1).'</td><td>'.pdf_h(pdf_thai_date($d, true)).'</td><td>'.pdf_h(pval($p, 'lecturer_name', '')).'</td><td></td><td>'.pdf_h(pdf_time($p['start_time'] ?? '')).' น.</td><td></td><td>'.pdf_h(pdf_time($p['end_time'] ?? '')).' น.</td></tr>';
    }

    return $h.'</table><p>จำนวน <span class="line">'.count($days).'</span> วัน จำนวน <span class="line">'.pdf_h(pval($p, 'course_hours', '')).'</span> ชั่วโมง</p>'.pdf_responsible_signature();
}

function pdf_pt10(array $p, array $students, bool $filled): string
{
    $h = pdf_form_code('พต. 10').'<div class="title">แบบประเมินผลการจัดการศึกษาเพื่อพัฒนาตนเอง</div><p class="center">หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid small"><tr><th>ที่</th><th>เลขประจำตัวประชาชน</th><th>ชื่อ - สกุล</th><th>1. ความรู้ความเข้าใจ (20)</th><th>2. ทักษะการปฏิบัติ (40)</th><th>3. คุณภาพผลงาน (40)</th><th>รวม (100)</th><th>ผลประเมิน</th></tr>';
    $rows = $students ?: array_fill(0, 10, []);
    foreach ($rows as $i => $s) {
        $a = $filled ? (float) ($s['knowledge_score'] ?? 0) : 0;
        $b = $filled ? (float) ($s['skill_score'] ?? 0) : 0;
        $c = $filled ? (float) ($s['attribute_score'] ?? 0) : 0;
        $t = $a + $b + $c;
        $h .= '<tr style="height:10mm"><td>'.($i + 1).'</td><td>'.pdf_h($s['id_card'] ?? '').'</td><td>'.pdf_h($s ? full_name($s) : '').'</td><td class="center">'.($filled ? pdf_h($a) : '').'</td><td class="center">'.($filled ? pdf_h($b) : '').'</td><td class="center">'.($filled ? pdf_h($c) : '').'</td><td class="center">'.($filled ? pdf_h($t) : '').'</td><td class="center">'.($filled ? ($t >= 60 ? 'ผ่าน' : 'ไม่ผ่าน') : '').'</td></tr>';
    }

    return $h.'</table><table class="two-signatures"><tr><td>ลงชื่อ ........................................ วิทยากรผู้สอน<br>('.pdf_h(pval($p, 'lecturer_name', '................................')).')</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['responsible_position']).'<br>('.pdf_h(SENA_ORG['responsible_name']).')</td></tr></table>'.pdf_signature_block('ผู้อนุมัติ');
}

function pdf_pt11(array $p, array $students): string
{
    $h = pdf_form_code('พต. 11').'<div class="title">บันทึกการนิเทศการฝึกอบรม</div><p class="center">'.pdf_h(SENA_ORG['district_full']).' &nbsp; '.pdf_h(SENA_ORG['learning_center']).'<br>สังกัด'.pdf_h(SENA_ORG['province_office']).'</p><p class="section">ตอนที่ ๑ ข้อมูลทั่วไป</p><p>๑. โครงการ/หลักสูตร <span class="line-long">'.pdf_h(pval($p, 'course_name')).'</span><br>๒. รูปแบบ <span class="checkbox"></span> การฝึกอบรมประชาชน <span class="checkbox"></span> การเรียนรู้รายบุคคล จำนวน <span class="line">'.pdf_h(pval($p, 'course_hours')).'</span> ชั่วโมง<br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>๓. ชื่อวิทยากร <span class="line-long">'.pdf_h(pval($p, 'lecturer_name')).'</span> สถานที่ <span class="line-long">'.pdf_h(pval($p, 'place')).'</span><br>จำนวนผู้เรียนที่สมัครเรียน <span class="line">'.count($students).'</span> คน จำนวนผู้เรียนที่มาเรียนในวันที่นิเทศ <span class="line">&nbsp;</span> คน</p><p class="section">ตอนที่ ๒ การจัดกระบวนการเรียนรู้</p><table class="grid small"><tr><th style="width:90mm">ประเด็นการนิเทศ</th><th>สภาพที่พบ</th><th>ข้อนิเทศ</th></tr>';
    foreach (['มีการจัดกิจกรรมตามโครงการ/แผนการจัดการเรียนรู้ตามหลักสูตรหรือไม่ อย่างไร', 'กิจกรรมสอดคล้องกับหลักสูตรหรือไม่', 'วิทยากรใช้สื่อ/วัสดุอุปกรณ์อย่างเหมาะสมหรือไม่', 'การถ่ายทอดความรู้และประสบการณ์ของวิทยากร', 'ผู้เรียนมีส่วนร่วมในการจัดกิจกรรมหรือไม่ อย่างไร', 'เครือข่ายมีส่วนร่วมในการจัดกิจกรรมหรือไม่ อย่างไร', 'การวัดผล ประเมินผล ทั้งภาคทฤษฎีและปฏิบัติ', 'อื่น ๆ (โปรดระบุ)'] as $i => $q) {
        $h .= '<tr style="height:13mm"><td>'.($i + 1).'. '.pdf_h($q).'</td><td></td><td></td></tr>';
    }

    return $h.'</table><p class="section">ข้อเสนอแนะเพื่อการพัฒนา</p><div class="blank-line"></div><div class="blank-line"></div><table class="two-signatures"><tr><td>ลงชื่อ ........................................ ประธานกรรมการ<br>ตำแหน่ง ........................................</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['supervisor_position']).'<br>('.pdf_h(SENA_ORG['supervisor_name']).')</td></tr></table>';
}

function pdf_pt12(array $p): string
{
    $items = ['ตอนที่ 1 ความพึงพอใจด้านเนื้อหา', 'เนื้อหาตรงตามความต้องการ', 'เนื้อหาเพียงพอต่อความต้องการ', 'เนื้อหาปัจจุบันทันสมัย', 'เนื้อหามีประโยชน์ต่อการนำไปใช้ในการพัฒนาคุณภาพชีวิต', 'ตอนที่ 2 ความพึงพอใจด้านกระบวนการจัดกิจกรรม', 'การเตรียมความพร้อมก่อนอบรม', 'การออกแบบกิจกรรมเหมาะสมกับวัตถุประสงค์', 'การจัดกิจกรรมเหมาะสมกับเวลา', 'การจัดกิจกรรมเหมาะสมกับกลุ่มเป้าหมาย', 'วิธีการวัดผล/ประเมินผลเหมาะสมกับวัตถุประสงค์', 'ตอนที่ 3 ความพึงพอใจต่อวิทยากร', 'วิทยากรมีความรู้ความสามารถในเรื่องที่ถ่ายทอด', 'วิทยากรมีเทคนิคการถ่ายทอดและใช้สื่อเหมาะสม', 'วิทยากรเปิดโอกาสให้มีส่วนร่วมและซักถาม', 'ตอนที่ 4 ความพึงพอใจด้านการอำนวยความสะดวก', 'สถานที่ วัสดุ อุปกรณ์และสิ่งอำนวยความสะดวก', 'การสื่อสารและบรรยากาศเพื่อให้เกิดการเรียนรู้', 'การบริการ การช่วยเหลือและการแก้ปัญหา'];
    $h = pdf_form_code('พต. 12').'<div class="title">แบบประเมินความพึงพอใจ</div><p class="center">หลักสูตร '.pdf_h(pval($p, 'course_name')).' ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null, true)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null, true)).'<br>สถานที่จัด '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><p class="bold">ข้อมูลพื้นฐานของผู้ประเมินความพึงพอใจ</p><p>เพศ <span class="checkbox"></span> ชาย <span class="checkbox"></span> หญิง อายุ <span class="line">&nbsp;</span> ปี วุฒิการศึกษา <span class="line">&nbsp;</span> อาชีพ <span class="line">&nbsp;</span></p><p><span class="bold">คำชี้แจง</span> โปรดทำเครื่องหมาย ✓ ในช่องระดับความพึงพอใจตามความคิดเห็นของท่าน</p><table class="grid small"><tr><th rowspan="2">รายการประเมินความพึงพอใจ</th><th colspan="5">ระดับความพึงพอใจ</th></tr><tr><th>มากที่สุด</th><th>มาก</th><th>ปานกลาง</th><th>น้อย</th><th>น้อยที่สุด</th></tr>';
    $n = 0;
    foreach ($items as $item) {
        if (str_starts_with($item, 'ตอนที่')) {
            $h .= '<tr><td colspan="6" class="bold">'.pdf_h($item).'</td></tr>';
        } else {
            $n++;
            $h .= '<tr><td>'.$n.'. '.pdf_h($item).'</td><td></td><td></td><td></td><td></td><td></td></tr>';
        }
    }

    return $h.'</table><p class="section">ความคิดเห็นและข้อเสนอแนะอื่น ๆ</p><div class="blank-line"></div><div class="blank-line"></div>';
}

function pdf_pt13(array $p, array $students): string
{
    $g = pdf_gender_counts($students);
    $total = pdf_project_total($p);
    $h = pdf_form_code('พต. 13(1)').'<div class="title">แบบรายงานผลการฝึกอบรม</div><div class="subtitle">'.pdf_h(SENA_ORG['learning_center']).'<br>'.pdf_h(SENA_ORG['district_full']).' จังหวัด'.pdf_h(SENA_ORG['province']).'<br>รูปแบบ '.pdf_h(pval($p, 'format_type')).'</div><p>1. หลักสูตร <span class="line-long">'.pdf_h(pval($p, 'course_name')).'</span> จำนวน <span class="line">'.pdf_h(pval($p, 'course_hours')).'</span> ชั่วโมง<br>2. ชื่อวิทยากร <span class="line-long">'.pdf_h(pval($p, 'lecturer_name')).'</span> วุฒิการศึกษา <span class="line">'.pdf_h(pval($p, 'lecturer_education')).'</span> อายุ <span class="line">'.pdf_h(pdf_age($p['lecturer_birthday'] ?? null)).'</span> ปี อาชีพ <span class="line">'.pdf_h(pval($p, 'lecturer_career')).'</span><br>3. ประเภทของวิทยากร <span class="checkbox"></span> ข้าราชการ <span class="checkbox"></span> ลูกจ้าง <span class="checkbox"></span> วิทยากรภายนอก <span class="checkbox"></span> อื่น ๆ<br>4. พื้นที่ดำเนินการ <span class="checkbox"></span> ในเขตเทศบาล <span class="checkbox"></span> นอกเขตเทศบาล ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'<br>5. ระยะเวลาดำเนินการ ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>6. อนุมัติเบิกจ่ายจากงบประมาณปี '.pdf_h($p['fiscal_year']).' กิจกรรมเรียนรู้เพื่อพัฒนาตนเอง งบรายจ่ายอื่น จำนวน '.pdf_money($total).' บาท ('.pdf_h(pdf_baht_text($total)).')<br>7. วิธีการสำรวจความต้องการเรียน <span class="checkbox"></span> ประชาคม <span class="checkbox"></span> แนะแนว <span class="checkbox"></span> สำรวจความต้องการ <span class="checkbox"></span> อื่น ๆ</p>';
    $h .= '<p class="section">8. จำนวนผู้เรียนและผู้ผ่านการฝึกอบรม จำแนกตามอายุและเพศ</p>'.pdf_summary_table(['ต่ำกว่า 15 ปี', '15-39 ปี', '40-59 ปี', '60 ปีขึ้นไป', 'รวม'], [['จำนวนผู้เรียน', '', '', '', '', $g['all']], ['จำนวนผู้ผ่านการฝึกอบรม', '', '', '', '', $g['all']]]);
    $h .= '<p class="section">9. จำแนกตามกลุ่มอาชีพและเพศ</p>'.pdf_summary_table(['รับราชการ', 'รัฐวิสาหกิจ', 'ค้าขาย', 'เกษตรกร', 'รับจ้าง', 'ธุรกิจส่วนตัว', 'แม่บ้าน', 'อื่น ๆ', 'รวม'], [['จำนวนผู้เรียน', '', '', '', '', '', '', '', '', $g['all']], ['จำนวนผู้ผ่านการฝึกอบรม', '', '', '', '', '', '', '', '', $g['all']]]);
    $h .= '<p class="section">10. จำแนกตามกลุ่มเป้าหมายและเพศ</p>'.pdf_summary_table(['ผู้นำท้องถิ่น', 'อบต.', 'ผู้ต้องขัง', 'ทหาร', 'แรงงานไทย', 'แรงงานต่างด้าว', 'เกษตรกร', 'อสม.', 'กลุ่มสตรี', 'ผู้สูงอายุ', 'อื่น ๆ', 'รวม'], [['จำนวนผู้เรียน', '', '', '', '', '', '', '', '', '', '', '', $g['all']], ['จำนวนผู้ผ่านการฝึกอบรม', '', '', '', '', '', '', '', '', '', '', '', $g['all']]]);
    $h .= '<pagebreak>'.pdf_form_code('พต. 13(2)').'<p class="section">11. จำนวนผู้เรียนและผู้ผ่านการฝึกอบรม จำแนกตามระดับการศึกษาและเพศ</p>'.pdf_summary_table(['ภูมิปัญญา', 'ประถมศึกษา', 'มัธยมต้น', 'มัธยมปลาย/ปวช.', 'อนุปริญญา/ปวส.', 'ปริญญาตรี', 'ปริญญาโท', 'ปริญญาเอก', 'รวม'], [['จำนวนผู้เรียน', '', '', '', '', '', '', '', '', $g['all']], ['จำนวนผู้ผ่านการฝึกอบรม', '', '', '', '', '', '', '', '', $g['all']]]).'<p class="section">12. การติดตามผู้ผ่านการฝึกอบรม</p><p>12.1 มีการติดตามผู้ผ่านการฝึกอบรม <span class="checkbox"></span> ไม่มี เพราะ <span class="line-long">&nbsp;</span> <span class="checkbox"></span> มี ดำเนินการอย่างไร <span class="line-long">&nbsp;</span><br>12.2 ผลการประเมินความพึงพอใจในการจัดโครงการ อยู่ในระดับ <span class="line-long">&nbsp;</span><br>12.3 ผู้ผ่านการฝึกอบรมได้นำความรู้ไปใช้จริง<br><span class="checkbox"></span> สร้างอาชีพ ........ คน <span class="checkbox"></span> เพิ่มรายได้ ........ คน <span class="checkbox"></span> ลดรายจ่าย ........ คน <span class="checkbox"></span> แก้ปัญหาในการดำรงชีวิต ........ คน<br><span class="checkbox"></span> มีทักษะ ........ คน <span class="checkbox"></span> ต่อยอดภูมิปัญญาท้องถิ่น ........ คน <span class="checkbox"></span> สร้างมูลค่าเพิ่ม ........ คน <span class="checkbox"></span> พัฒนาสู่วิสาหกิจชุมชน ........ คน</p><p>12.4 รายได้เฉลี่ยเพิ่มขึ้น/เดือน<br><span class="checkbox"></span> ต่ำกว่า 1,000 บาท ........ คน <span class="checkbox"></span> 1,001-3,000 บาท ........ คน <span class="checkbox"></span> 3,001-5,000 บาท ........ คน<br><span class="checkbox"></span> 5,001-10,000 บาท ........ คน <span class="checkbox"></span> 10,001-15,000 บาท ........ คน <span class="checkbox"></span> 15,001-30,000 บาท ........ คน <span class="checkbox"></span> 30,001 บาทขึ้นไป ........ คน</p>';
    $h .= '<p class="section">13. ปัญหา อุปสรรค และข้อเสนอแนะ</p><p>13.1 ปัญหา อุปสรรค <span class="checkbox"></span> ไม่มี <span class="checkbox"></span> มี โปรดระบุ</p><div class="blank-line"></div><p>13.2 ข้อเสนอแนะ</p><div class="blank-line"></div><table class="two-signatures"><tr><td>ลงชื่อ ........................................ วิทยากร<br>('.pdf_h(pval($p, 'lecturer_name', '................................')).')</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['responsible_position']).'<br>('.pdf_h(SENA_ORG['responsible_name']).')</td></tr><tr><td style="padding-top:10mm">ลงชื่อ ........................................<br>('.pdf_h(SENA_ORG['responsible_name']).')<br>'.pdf_h(SENA_ORG['responsible_position']).'</td><td style="padding-top:10mm">ลงชื่อ ........................................<br>('.pdf_h(SENA_ORG['director_name']).')<br>'.pdf_h(SENA_ORG['director_position']).'</td></tr></table>';

    return $h;
}

function pdf_summary_table(array $headers, array $rows): string
{
    $h = '<table class="grid tiny"><tr><th>รายการ</th>';
    foreach ($headers as $x) {
        $h .= '<th>'.pdf_h($x).'</th>';
    }$h .= '</tr>';
    foreach ($rows as $row) {
        $h .= '<tr>';
        foreach ($row as $cell) {
            $h .= '<td class="center">'.pdf_h($cell).'</td>';
        }$h .= '</tr>';
    }

return $h.'</table>';
}

function pdf_pt14(array $p, array $students): string
{
    if (! $students) {
        $students = [['prefix' => '', 'first_name' => '', 'last_name' => '']];
    }$h = '';
    foreach ($students as $i => $s) {
        if ($i) {
            $h .= '<pagebreak orientation="L" sheet-size="A4-L" />';
        }$h .= '<div class="certificate"><div class="cert-frame"><p class="right">เลขที่ ........................</p><img width="88" class="cert-garuda" src="'.pdf_h(realpath(pdf_asset_path('images/garuda.png'))).'"><div class="cert-org">'.pdf_h(SENA_ORG['district_full']).'</div><div class="cert-text">ใบสำคัญฉบับนี้ให้ไว้เพื่อแสดงว่า</div><div class="cert-person">'.pdf_h(full_name($s)).'</div><div class="cert-text">ผ่านการฝึกอบรมหลักสูตร “'.pdf_h(pval($p, 'course_name')).'” จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>เมื่อวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>ขอให้มีความสุข ความเจริญ ก้าวหน้าสืบไป</div><table class="two-signatures" style="margin-top:12mm"><tr><td>ลงชื่อ ........................................................<br>('.pdf_h(SENA_ORG['responsible_name']).')<br>'.pdf_h(SENA_ORG['responsible_position']).'</td><td>ลงชื่อ ........................................................<br>('.pdf_h(SENA_ORG['director_name']).')<br>'.pdf_h(SENA_ORG['director_position']).'</td></tr></table></div></div>';
    }

    return $h;
}

function pdf_pt15(array $p, array $students): string
{
    $g = pdf_gender_counts($students);
    $total = pdf_project_total($p);
    $h = pdf_letter_header($p, 'ส่งหลักฐานการอนุมัติเบิกจ่ายเงินงบประมาณ', 'ผู้อำนวยการ'.SENA_ORG['province_office'], 'พต. 15').'<table class="attachments"><tr><td style="width:32mm" class="bold">สิ่งที่ส่งมาด้วย</td><td>หลักฐานการอนุมัติการเบิกจ่ายเงิน</td><td class="right">จำนวน 1 ชุด</td></tr></table><div class="letter-body"><p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['district_short']).' ขอส่งหลักฐานการอนุมัติเบิกจ่ายเงินงบประมาณ งบรายจ่ายอื่น ('.pdf_h(pval($p, 'format_type')).') ซึ่งได้ดำเนินการจัดการเรียนรู้เพื่อพัฒนาตนเอง หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. จำนวน '.$g['all'].' คน และมี '.pdf_h(pval($p, 'lecturer_name')).' เป็นวิทยากร ดังรายการต่อไปนี้</p>';
    $budget = array_filter(pdf_budget_rows($p), static fn (float $amount): bool => $amount > 0);
    $h .= '<table class="memo-budget">';
    foreach (array_chunk($budget, 2, true) as $row) {
        $h .= '<tr>';
        foreach ($row as $n => $m) {
            $h .= '<td>'.pdf_h($n).' <span class="line">'.pdf_money($m).'</span> บาท</td>';
        }if (count($row) === 1) {
            $h .= '<td></td>';
        }$h .= '</tr>';
    }
    $h .= '</table>';

    return $h.'<p class="thai-distributed">รวมเป็นเงินทั้งสิ้น '.pdf_money($total).' บาท ('.pdf_h(pdf_baht_text($total)).') จากเงินงบประมาณ แผนงาน : ยุทธศาสตร์พัฒนาคุณภาพการศึกษาและการเรียนรู้ กิจกรรมเรียนรู้เพื่อพัฒนาตนเอง งบรายจ่ายอื่น</p><p class="thai-distributed">จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ</p></div>'.pdf_external_signature_block().pdf_org_footer();
}

function pdf_pt16(array $p, array $students): string
{
    $g = pdf_gender_counts($students);
    $total = pdf_project_total($p);
    $h = pdf_memo_header('พต. 16').pdf_memo_meta($p, 'ขออนุญาตเบิกจ่ายเงินค่าการจัดการศึกษาต่อเนื่อง รูปแบบ'.pval($p, 'format_type').' หลักสูตร'.pval($p, 'course_name').' จำนวน '.pval($p, 'course_hours').' ชม.').'<div class="body"><p class="thai-distributed">ตามที่ '.pdf_h(SENA_ORG['learning_center']).' ได้จัดกิจกรรมการเรียนรู้เพื่อพัฒนาตนเอง รูปแบบ '.pdf_h(pval($p, 'format_type')).' หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง ให้กับประชาชน ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' มีผู้เรียน '.$g['all'].' คน ชาย '.$g['male'].' คน หญิง '.$g['female'].' คน โดยมี '.pdf_h(pval($p, 'lecturer_name')).' เป็นวิทยากร</p><p class="thai-distributed">บัดนี้ การดำเนินงานได้เสร็จสิ้นแล้ว โดยมีค่าใช้จ่ายตามรายการ ดังนี้</p>';
    $budget = array_filter(pdf_budget_rows($p), static fn (float $amount): bool => $amount > 0);
    $h .= '<table class="memo-budget">';
    foreach (array_chunk($budget, 2, true) as $row) {
        $h .= '<tr>';
        foreach ($row as $n => $m) {
            $h .= '<td>'.pdf_h($n).' <span class="line">'.pdf_money($m).'</span> บาท</td>';
        }if (count($row) === 1) {
            $h .= '<td></td>';
        }$h .= '</tr>';
    }
    $h .= '</table>';

    return $h.'<p class="thai-distributed">โดยขอใช้เงินงบประมาณปี '.pdf_h($p['fiscal_year']).' ภายในวงเงิน '.pdf_money($total).' บาท ('.pdf_h(pdf_baht_text($total)).') จึงเรียนมาเพื่อโปรดทราบและพิจารณาอนุมัติ</p></div>'.pdf_responsible_signature().'<table class="two-signatures"><tr><td>เรียน ผู้อำนวยการ'.pdf_h(SENA_ORG['district_short']).'<br>ได้ตรวจสอบแล้ว ถูกต้องตามระเบียบราชการ<br>จึงเห็นควรอนุมัติ<br><br>ลงชื่อ ........................................<br>('.pdf_h(SENA_ORG['finance_name']).')<br>'.pdf_h(SENA_ORG['finance_position']).'</td><td>ข้อคิดเห็นผู้บริหาร<br><br><br>ลงชื่อ ........................................<br>('.pdf_h(SENA_ORG['director_name']).')<br>'.pdf_h(SENA_ORG['director_position']).'</td></tr></table>';
}

function pdf_pt17(array $p): string
{
    $h = pdf_form_code('พต. 17').'<div class="title">สรุปงบหน้าการเบิกเงิน</div><p class="center">ค่าตอบแทนวิทยากรกลุ่ม หลักสูตร '.pdf_h(pval($p, 'course_name')).'<br>'.pdf_h(SENA_ORG['district_full']).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง</p><p><span class="bold">รหัสงบประมาณ</span> แผนงาน : ยุทธศาสตร์พัฒนาคุณภาพการศึกษาและการเรียนรู้ ผลผลิตผู้รับบริการการเรียนรู้เพื่อพัฒนาคุณภาพชีวิต กิจกรรมเรียนรู้เพื่อพัฒนาตนเอง งบรายจ่ายอื่น ('.pdf_h(pval($p, 'format_type')).')</p><table class="grid"><tr><th>ที่</th><th>ชื่อ-สกุล</th><th>ตำแหน่ง</th><th>ค่าตอบแทน</th><th>หมายเลขบัญชีธนาคาร</th></tr><tr style="height:10mm"><td>1</td><td>'.pdf_h(pval($p, 'lecturer_name')).'</td><td>วิทยากร</td><td class="right">'.pdf_money($p['lecturer_cost']).'</td><td></td></tr>';
    for ($i = 2; $i <= 10; $i++) {
        $h .= '<tr style="height:9mm"><td>'.$i.'</td><td></td><td></td><td></td><td></td></tr>';
    }

    return $h.'<tr><th colspan="3">รวมเป็นเงิน</th><th class="right">'.pdf_money($p['lecturer_cost']).'</th><th></th></tr></table><p class="center">( '.pdf_h(pdf_baht_text($p['lecturer_cost'])).' )</p>'.pdf_responsible_signature();
}

function pdf_pt18(array $p, bool $evidence): string
{
    $amount = (float) $p['lecturer_cost'];
    $hours = max(1, (int) ($p['course_hours'] ?: 1));
    $rate = $amount / $hours;
    $h = pdf_form_code('พต. 18').'<div class="title">ใบสำคัญรับเงิน</div><div class="subtitle">'.pdf_h(SENA_ORG['district_short']).'</div><p class="right">วันที่ ........................................................</p><p>ข้าพเจ้า <span class="line-long">'.pdf_h(pval($p, 'lecturer_name')).'</span> เลขบัตรประชาชน <span class="line-long">'.pdf_h(pval($p, 'lecturer_id_card')).'</span><br>ที่อยู่ <span class="line-long" style="min-width:135mm">'.pdf_h(pval($p, 'lecturer_address')).'</span><br>ได้รับเงินจาก '.pdf_h(SENA_ORG['district_full']).' ดังรายการต่อไปนี้</p><table class="grid"><tr><th>รายการ</th><th style="width:40mm">จำนวนเงิน</th></tr><tr style="height:50mm"><td>ได้รับเงินค่าตอบแทนวิทยากรหลักสูตร '.pdf_h(pval($p, 'course_name')).'<br>ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'<br>ในวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).'<br>ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. - '.pdf_h(pdf_time($p['end_time'] ?? '')).' น.<br>จำนวน '.pdf_h($hours).' ชั่วโมง ๆ ละ '.pdf_money($rate).' บาท</td><td class="right">'.pdf_money($amount).'</td></tr><tr><th>รวมเงิน</th><th class="right">'.pdf_money($amount).'</th></tr></table><p>จำนวนเงินเป็นตัวอักษร ( '.pdf_h(pdf_baht_text($amount)).' )</p><table class="two-signatures"><tr><td>ลงชื่อ ........................................ ผู้รับเงิน<br>('.pdf_h(pval($p, 'lecturer_name')).')</td><td>ลงชื่อ ........................................ ผู้จ่ายเงิน<br>(........................................................)</td></tr></table>';
    if ($evidence) {
        $h .= '<div style="border-top:0.3mm solid #222;margin-top:12mm;padding-top:5mm"><p class="thai-distributed">ข้าพเจ้าขอรับรองว่าได้นำเงิน '.pdf_money($amount).' บาท ('.pdf_h(pdf_baht_text($amount)).') จ่ายเป็นค่าตอบแทนวิทยากรหลักสูตร '.pdf_h(pval($p, 'course_name')).' ในวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' จริง</p><div class="signature">ลงชื่อ ........................................ ผู้รับรอง<br>(........................................................)</div></div>';
    } else {
        $h .= '<p style="margin-top:12mm;font-size:13pt">หมายเหตุ : แนบสำเนาบัตรประชาชนของวิทยากรพร้อมลงชื่อรับรองสำเนาถูกต้องและหนังสือเชิญวิทยากร</p>';
    }

    return $h;
}

function pdf_pt23(array $p): string
{
    $subject = 'แจ้งการจัดการศึกษาเพื่อพัฒนาตนเอง รูปแบบ'.pval($p, 'format_type').' หลักสูตร'.pval($p, 'course_name').' จำนวน '.pval($p, 'course_hours').' ชม.';
    $one = function (string $copy = '') use ($p, $subject) {
        return pdf_letter_header($p, $subject, 'ผู้อำนวยการ '.SENA_ORG['province_office'], '', $copy).'<table class="attachments"><tr><td style="width:32mm" class="bold">สิ่งที่ส่งมาด้วย</td><td>หลักฐานการขออนุญาตจัดการศึกษาเพื่อพัฒนาตนเอง</td><td class="right">จำนวน 1 ชุด</td></tr></table><div class="letter-body"><p class="thai-distributed">ด้วย '.pdf_h(SENA_ORG['district_short']).' จัดการศึกษาเพื่อพัฒนาตนเอง รูปแบบ '.pdf_h(pval($p, 'format_type')).' หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).' ในระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. ถึง '.pdf_h(pdf_time($p['end_time'] ?? '')).' น. โดยมี '.pdf_h(pval($p, 'lecturer_name')).' เป็นวิทยากรให้ความรู้</p><p class="thai-distributed">จึงเรียนมาเพื่อโปรดทราบ</p></div>'.pdf_external_signature_block().pdf_org_footer();
    };

    return $one().'<pagebreak>'.$one('สำเนาคู่ฉบับ');
}

function pdf_pt24(array $p, array $students): string
{
    $h = '<div class="title">ทะเบียนผู้จบหลักสูตรการจัดการศึกษาเพื่อพัฒนาตนเอง</div><div class="subtitle">'.pdf_h(SENA_ORG['district_full']).' จังหวัด'.pdf_h(SENA_ORG['province']).'</div><p class="center">โครงการ '.pdf_h(pval($p, 'title')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid tiny"><tr><th>ที่</th><th>ชื่อ - สกุล</th><th>เลขบัตรประชาชน</th><th>อายุ</th><th>วุฒิการศึกษา</th><th>อาชีพ</th><th>ที่อยู่ปัจจุบัน</th><th>ผ่าน</th><th>ไม่ผ่าน</th><th>เลขที่หลักฐานสำคัญ</th></tr>';
    $rows = $students ?: array_fill(0, 10, []);
    foreach ($rows as $i => $s) {
        $t = (float) ($s['knowledge_score'] ?? 0) + (float) ($s['skill_score'] ?? 0) + (float) ($s['attribute_score'] ?? 0);
        $h .= '<tr style="height:10mm"><td>'.($i + 1).'</td><td>'.pdf_h($s ? full_name($s) : '').'</td><td>'.pdf_h($s['id_card'] ?? '').'</td><td>'.pdf_h(pdf_age($s['birthday'] ?? null)).'</td><td>'.pdf_h($s['education'] ?? '').'</td><td>'.pdf_h($s['career'] ?? '').'</td><td>'.pdf_h($s['address'] ?? '').'</td><td class="center">'.($s && $t >= 60 ? '✓' : '').'</td><td class="center">'.($s && $t < 60 ? '✓' : '').'</td><td></td></tr>';
    }

    return $h.'</table>'.pdf_responsible_signature();
}

function pdf_time_blank(array $p, array $students, bool $two): string
{
    $h = '<div class="title">บัญชีลงเวลาของผู้เรียนการจัดการศึกษาเพื่อพัฒนาตนเอง</div><p class="center">หลักสูตร '.pdf_h(pval($p, 'course_name')).' จำนวน '.pdf_h(pval($p, 'course_hours')).' ชั่วโมง<br>วิทยากร '.pdf_h(pval($p, 'lecturer_name')).' จำนวนผู้เรียน '.count($students).' คน<br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. - '.pdf_h(pdf_time($p['end_time'] ?? '')).' น.<br>สถานที่จัด ณ '.pdf_h(pval($p, 'place')).' '.pdf_h(pval($p, 'address')).'</p><table class="grid"><tr><th rowspan="2">ที่</th><th rowspan="2">ชื่อ - สกุล</th><th colspan="3">วันที่ ........................................</th>'.($two ? '<th colspan="3">วันที่ ........................................</th>' : '').'</tr><tr><th>ลายมือชื่อ</th><th>เวลามา</th><th>เวลากลับ</th>'.($two ? '<th>ลายมือชื่อ</th><th>เวลามา</th><th>เวลากลับ</th>' : '').'</tr>';
    $rows = $students ?: array_fill(0, 10, []);
    foreach ($rows as $i => $s) {
        $h .= '<tr style="height:10mm"><td>'.($i + 1).'</td><td>'.pdf_h($s ? full_name($s) : '').'</td><td></td><td></td><td></td>'.($two ? '<td></td><td></td><td></td>' : '').'</tr>';
    }

    return $h.'</table><table class="two-signatures"><tr><td>ลงชื่อ ........................................ วิทยากรผู้สอน<br>('.pdf_h(pval($p, 'lecturer_name', '................................')).')</td><td>ลงชื่อ ........................................ '.pdf_h(SENA_ORG['responsible_position']).'<br>('.pdf_h(SENA_ORG['responsible_name']).')</td></tr></table>';
}

function pdf_lecturer_time_blank(array $p): string
{
    return pdf_pt92(array_merge($p, ['start_date' => null, 'end_date' => null]));
}

function pdf_photo_report(array $p, string $type): string
{
    $photos = pdf_photos((int) $p['id'], $type);
    $label = $type === 'material' ? 'ภาพวัสดุ' : 'ภาพกิจกรรม';
    $h = '<div class="title">'.$label.'การจัดการศึกษาเพื่อพัฒนาตนเอง '.pdf_h(SENA_ORG['learning_center']).'</div><p class="center">หลักสูตร '.pdf_h(pval($p,'course_name')).' จำนวน '.pdf_h(pval($p,'course_hours')).' ชั่วโมง<br>ระหว่างวันที่ '.pdf_h(pdf_thai_date($p['start_date'] ?? null)).' ถึง '.pdf_h(pdf_thai_date($p['end_date'] ?? null)).' ตั้งแต่เวลา '.pdf_h(pdf_time($p['start_time'] ?? '')).' น. - '.pdf_h(pdf_time($p['end_time'] ?? '')).' น.<br>สถานที่จัด ณ '.pdf_h(pval($p,'place')).' '.pdf_h(pval($p,'address')).'</p>';
    if (! $photos) {
        $photos = array_fill(0,4,['file_path' => '', 'caption' => '']);
    }$chunks = array_chunk($photos,4);
    foreach ($chunks as $ci => $chunk) {
        if ($ci) {
            $h .= '<pagebreak><div class="title">'.$label.'การจัดการศึกษาเพื่อพัฒนาตนเอง</div>';
        }$h .= '<table class="photo-grid">';
        foreach (array_chunk($chunk,2) as $row) {
            $h .= '<tr>';
            foreach ($row as $photo) {
                $path = (string) ($photo['file_path'] ?? '');
                $abs = $path !== '' ? pdf_upload_path($path) : false;
                $placeholder = '<table class="photo-placeholder-table"><tr><td>พื้นที่สำหรับติดภาพ</td></tr></table>';
                $h .= '<td>'.($abs ? '<img src="'.pdf_h($abs).'">' : $placeholder).'<br>'.pdf_h($photo['caption'] ?? '').'</td>';
            }$h .= '</tr>';
        }$h .= '</table>';
    }

    return $h;
}
