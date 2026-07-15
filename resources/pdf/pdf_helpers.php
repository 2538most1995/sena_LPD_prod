<?php
declare(strict_types=1);

function pdf_connection(): PDO
{
    return \Illuminate\Support\Facades\DB::connection()->getPdo();
}

function full_name(array $row): string
{
    return trim(($row['prefix'] ?? '').($row['first_name'] ?? '').' '.($row['last_name'] ?? ''));
}

function pdf_asset_path(string $relativePath): string
{
    return __DIR__.'/'.ltrim($relativePath, '/');
}

function pdf_upload_path(string $relativePath): string|false
{
    if (! str_starts_with($relativePath, 'uploads/')) {
        return false;
    }

    $uploadRoot = realpath(storage_path('app/private/uploads'));
    $path = realpath(storage_path('app/private/'.$relativePath));

    return $uploadRoot && $path && str_starts_with($path, $uploadRoot.DIRECTORY_SEPARATOR) && is_file($path)
        ? $path
        : false;
}

const SENA_ORG_DEFAULTS = [
    'learning_center' => 'ศกร.ระดับตำบลเสนา',
    'district_short' => 'สกร.ระดับอำเภอเสนา',
    'district_full' => 'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา',
    'province_office' => 'สำนักงานส่งเสริมการเรียนรู้ประจำจังหวัดพระนครศรีอยุธยา',
    'province' => 'พระนครศรีอยุธยา',
    'official_no' => 'ศธ 07093.05',
    'address' => 'อำเภอเสนา จังหวัดพระนครศรีอยุธยา 13110',
    'phone' => '035-201-671',
    'fax' => '035-201-671',
    'responsible_name' => '........................................................',
    'responsible_position' => 'ครูศูนย์การเรียนรู้',
    'director_name' => '........................................................',
    'director_position' => 'ผู้อำนวยการศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา',
    'owner_unit' => 'งานการเรียนรู้เพื่อการพัฒนาตนเอง',
    'registrar_name' => '........................................................',
    'registrar_position' => 'ผู้รับสมัคร',
    'finance_name' => '........................................................',
    'finance_position' => 'เจ้าหน้าที่การเงิน',
    'payer_name' => '........................................................',
    'payer_position' => 'ผู้จ่ายเงิน',
    'certifier_name' => '........................................................',
    'certifier_position' => 'ผู้รับรอง',
    'supervisor_name' => '........................................................',
    'supervisor_position' => 'ผู้นิเทศ',
    'follow_up_name' => '........................................................',
    'follow_up_position' => 'ผู้ติดตามผล',
];

function pdf_document_profile(int $userId): array
{
    $stmt = pdf_connection()->prepare(
        "SELECT u.school_name user_school_name, u.teacher_name user_teacher_name,
                u.position user_position, u.address_line user_address_line,
                u.subdistrict user_subdistrict, u.district user_district,
                u.province user_province, u.postal_code user_postal_code,
                u.phone user_phone,
                parent.school_name parent_school_name,
                parent.teacher_name parent_teacher_name,
                parent.position parent_position,
                ds.learning_center_name setting_learning_center_name,
                ds.district_office_name setting_district_office_name,
                ds.district_office_short_name setting_district_office_short_name,
                ds.province_office_name setting_province_office_name,
                ds.document_no_prefix setting_document_no_prefix,
                ds.office_address setting_office_address,
                ds.phone setting_phone, ds.fax setting_fax,
                ds.owner_unit setting_owner_unit,
                ds.registrar_name setting_registrar_name,
                ds.registrar_position setting_registrar_position,
                ds.responsible_name setting_responsible_name,
                ds.responsible_position setting_responsible_position,
                ds.director_name setting_director_name,
                ds.director_position setting_director_position,
                ds.finance_officer_name setting_finance_name,
                ds.finance_officer_position setting_finance_position,
                ds.payer_name setting_payer_name,
                ds.payer_position setting_payer_position,
                ds.certifier_name setting_certifier_name,
                ds.certifier_position setting_certifier_position,
                ds.supervisor_name setting_supervisor_name,
                ds.supervisor_position setting_supervisor_position,
                ds.follow_up_name setting_follow_up_name,
                ds.follow_up_position setting_follow_up_position
         FROM users u
         LEFT JOIN users parent ON parent.id=u.parent_id
         LEFT JOIN document_settings ds ON ds.user_id=u.id
         WHERE u.id=?"
    );
    $stmt->execute([$userId]);

    return $stmt->fetch() ?: [];
}

function pdf_define_org_context(array $profile = []): void
{
    if (defined('SENA_ORG')) {
        return;
    }

    $pick = static function (string $key, string $fallback = '') use ($profile): string {
        $value = trim((string) ($profile[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    };
    $person = static fn (string $key, string $fallback = ''): string => $pick(
        $key,
        $fallback !== '' ? $fallback : '........................................................'
    );
    $address = trim(implode(' ', array_filter([
        $pick('user_address_line'),
        $pick('user_subdistrict') !== '' ? 'ต.'.$pick('user_subdistrict') : '',
        $pick('user_district') !== '' ? 'อ.'.$pick('user_district') : '',
        $pick('user_province') !== '' ? 'จ.'.$pick('user_province') : '',
        $pick('user_postal_code'),
    ])));
    $districtFull = $pick('setting_district_office_name', $pick('parent_school_name', SENA_ORG_DEFAULTS['district_full']));
    $responsibleName = $person('setting_responsible_name', $pick('user_teacher_name'));
    $responsiblePosition = $pick('setting_responsible_position', $pick('user_position', SENA_ORG_DEFAULTS['responsible_position']));

    define('SENA_ORG', [
        'learning_center' => $pick('setting_learning_center_name', $pick('user_school_name', SENA_ORG_DEFAULTS['learning_center'])),
        'district_short' => $pick('setting_district_office_short_name', SENA_ORG_DEFAULTS['district_short']),
        'district_full' => $districtFull,
        'province_office' => $pick('setting_province_office_name', SENA_ORG_DEFAULTS['province_office']),
        'province' => $pick('user_province', SENA_ORG_DEFAULTS['province']),
        'official_no' => $pick('setting_document_no_prefix', SENA_ORG_DEFAULTS['official_no']),
        'address' => $pick('setting_office_address', $address !== '' ? $address : SENA_ORG_DEFAULTS['address']),
        'phone' => $pick('setting_phone', $pick('user_phone', SENA_ORG_DEFAULTS['phone'])),
        'fax' => $pick('setting_fax', $pick('user_phone', SENA_ORG_DEFAULTS['fax'])),
        'owner_unit' => $pick('setting_owner_unit', SENA_ORG_DEFAULTS['owner_unit']),
        'registrar_name' => $person('setting_registrar_name', $responsibleName),
        'registrar_position' => $pick('setting_registrar_position', SENA_ORG_DEFAULTS['registrar_position']),
        'responsible_name' => $responsibleName,
        'responsible_position' => $responsiblePosition,
        'director_name' => $person('setting_director_name', $pick('parent_teacher_name')),
        'director_position' => $pick('setting_director_position', $pick('parent_position', 'ผู้อำนวยการ'.$districtFull)),
        'finance_name' => $person('setting_finance_name'),
        'finance_position' => $pick('setting_finance_position', SENA_ORG_DEFAULTS['finance_position']),
        'payer_name' => $person('setting_payer_name'),
        'payer_position' => $pick('setting_payer_position', SENA_ORG_DEFAULTS['payer_position']),
        'certifier_name' => $person('setting_certifier_name'),
        'certifier_position' => $pick('setting_certifier_position', SENA_ORG_DEFAULTS['certifier_position']),
        'supervisor_name' => $person('setting_supervisor_name'),
        'supervisor_position' => $pick('setting_supervisor_position', SENA_ORG_DEFAULTS['supervisor_position']),
        'follow_up_name' => $person('setting_follow_up_name', $responsibleName),
        'follow_up_position' => $pick('setting_follow_up_position', $responsiblePosition),
    ]);
}

function pdf_h(mixed $value): string
{
    $text = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $value));

    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pdf_project(int $projectId): array
{
    $stmt = pdf_connection()->prepare(
        "SELECT p.*, c.name course_name, c.hours course_hours, c.category course_category,
                c.description course_description,
                CONCAT(l.prefix,l.first_name,' ',l.last_name) lecturer_name,
                l.id_card lecturer_id_card,l.phone lecturer_phone,l.address lecturer_address,
                l.birthday lecturer_birthday,l.education lecturer_education,
                l.career lecturer_career,l.expertise lecturer_expertise
         FROM projects p
         JOIN courses c ON c.id=p.course_id
         JOIN lecturers l ON l.id=p.lecturer_id
         WHERE p.id=?"
    );
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    if (!$project) {
        throw new RuntimeException('ไม่พบข้อมูลกิจกรรมที่เลือก');
    }
    return $project;
}

function pdf_students(int $projectId): array
{
    $stmt = pdf_connection()->prepare(
        "SELECT s.*,sc.knowledge_score,sc.skill_score,sc.attribute_score
         FROM project_students ps
         JOIN students s ON s.id=ps.student_id
         LEFT JOIN scores sc ON sc.project_id=ps.project_id AND sc.student_id=ps.student_id
         WHERE ps.project_id=? ORDER BY s.first_name,s.last_name"
    );
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function pdf_photos(int $projectId, string $type): array
{
    $stmt = pdf_connection()->prepare('SELECT * FROM activity_photos WHERE project_id=? AND photo_type=? ORDER BY sort_order,id');
    $stmt->execute([$projectId, $type]);
    return $stmt->fetchAll();
}

function pdf_blank_project(): array
{
    return [
        'id'=>0,'title'=>'','objective'=>'','format_type'=>'หลักสูตร 3-9 ชั่วโมง',
        'attribute_type'=>'สถานศึกษาเป็นผู้จัด','activity_type'=>'การศึกษาเพื่อพัฒนาอาชีพ',
        'place'=>'','address'=>'','latitude'=>'','longitude'=>'','start_date'=>null,'end_date'=>null,
        'start_time'=>'09:00:00','end_time'=>'12:00:00','fiscal_year'=>(int)date('Y')+543,
        'lecturer_cost'=>0,'material_cost'=>0,'board_cost'=>0,'food_cost'=>0,'snack_cost'=>0,
        'place_cost'=>0,'transport_cost'=>0,'other_cost'=>0,'course_name'=>'','course_hours'=>'',
        'course_category'=>'','course_description'=>'','lecturer_name'=>'','lecturer_id_card'=>'',
        'lecturer_phone'=>'','lecturer_address'=>'','lecturer_birthday'=>null,'lecturer_education'=>'',
        'lecturer_career'=>'','lecturer_expertise'=>'',
    ];
}

function pdf_thai_date(?string $date, bool $short = false): string
{
    if (!$date) return '........................................';
    $long = [1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $abbr = [1=>'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $ts = strtotime($date);
    return (int)date('j',$ts).' '.($short?$abbr:$long)[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543);
}

function pdf_time(mixed $time): string
{
    $value = substr((string)$time, 0, 5);
    return $value !== '' ? $value : '........';
}

function pdf_age(?string $birthday): string
{
    if (!$birthday) return '';
    return (string)(new DateTime($birthday))->diff(new DateTime())->y;
}

function pdf_days(?string $start, ?string $end): array
{
    if (!$start || !$end) return [];
    $first = new DateTime($start);
    $last = (new DateTime($end))->modify('+1 day');
    $days = [];
    foreach (new DatePeriod($first, new DateInterval('P1D'), $last) as $date) $days[] = $date->format('Y-m-d');
    return $days;
}

function pdf_gender_counts(array $students): array
{
    $male = count(array_filter($students, fn($s) => ($s['gender'] ?? '') === 'ชาย'));
    $female = count(array_filter($students, fn($s) => ($s['gender'] ?? '') === 'หญิง'));
    return ['all'=>count($students),'male'=>$male,'female'=>$female];
}

function pdf_money(float|int|string $amount): string
{
    return number_format((float)$amount, 2);
}

function pdf_baht_text(float|int|string $amount): string
{
    $amount = number_format((float)$amount, 2, '.', '');
    [$integer, $fraction] = explode('.', $amount);
    $digits = ['ศูนย์','หนึ่ง','สอง','สาม','สี่','ห้า','หก','เจ็ด','แปด','เก้า'];
    $places = ['','สิบ','ร้อย','พัน','หมื่น','แสน'];
    $read = function(string $number) use (&$read,$digits,$places): string {
        $number = ltrim($number, '0');
        if ($number === '') return '';
        if (strlen($number) > 6) {
            $head = substr($number, 0, -6);
            $tail = substr($number, -6);
            return $read($head).'ล้าน'.$read($tail);
        }
        $out=''; $len=strlen($number);
        for($i=0;$i<$len;$i++) {
            $n=(int)$number[$i]; $pos=$len-$i-1;
            if($n===0) continue;
            if($pos===1 && $n===1) $out.='สิบ';
            elseif($pos===1 && $n===2) $out.='ยี่สิบ';
            elseif($pos===0 && $n===1 && $len>1) $out.='เอ็ด';
            else $out.=$digits[$n].$places[$pos];
        }
        return $out;
    };
    $text = $read($integer).'บาท';
    return $text.($fraction === '00' ? 'ถ้วน' : $read($fraction).'สตางค์');
}

function pdf_budget_rows(array $p): array
{
    return [
        'ค่าวัสดุ'=>(float)$p['material_cost'],
        'ค่าตอบแทนวิทยากร'=>(float)$p['lecturer_cost'],
        'ค่าอาหารกลางวัน'=>(float)$p['food_cost'],
        'ค่าอาหารว่างและเครื่องดื่ม'=>(float)$p['snack_cost'],
        'ค่าป้าย'=>(float)$p['board_cost'],
        'ค่าจ้างเหมารถ'=>(float)$p['transport_cost'],
        'ค่าสถานที่'=>(float)$p['place_cost'],
        'ค่าใช้จ่ายอื่น ๆ'=>(float)$p['other_cost'],
    ];
}

function pdf_project_total(array $p): float
{
    return array_sum(pdf_budget_rows($p));
}

function pdf_base_css(): string
{
    return <<<'CSS'
@page { size: A4; margin: 14mm 16mm 14mm 22mm; }
body { font-family: thsarabunnew135zws; font-size: 16pt; line-height: 1.05; color: #000; }
h1,h2,h3,p { margin: 0; padding: 0; }
.page { page-break-after: always; }
.page:last-child { page-break-after: auto; }
.form-code { position: absolute; right: 0; top: 0; width: 43mm; text-align:center; border: 0.35mm solid #222; padding: 2.2mm 3mm; font-weight: bold; font-size: 16pt; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: bold; }
.title { text-align:center; font-weight:bold; font-size:20pt; line-height:1.0; margin: 2mm 0; }
.subtitle { text-align:center; font-weight:bold; font-size:17pt; line-height:1.0; }
.line { border-bottom:0.25mm dotted #333; min-width:25mm; display:inline-block; text-align:center; padding:0 1mm; }
.line-long { border-bottom:0.25mm dotted #333; display:inline-block; min-width:85mm; text-align:center; }
.memo-head { position:relative; min-height:30mm; }
.memo-head img { position:absolute; left:0; top:0; width:25mm; }
.memo-title { text-align:center; font-weight:bold; font-size:29pt; padding-top:10mm; }
.memo-row { font-size:17pt; line-height:1.1; margin-bottom:1mm; }
.memo-label { font-weight:bold; }
.rule { border-bottom:0.35mm solid #222; height:1mm; margin-bottom:2mm; }
.body { text-align:justify; line-height:1.18; }
.indent { text-indent:20mm; }
.signature { width:72mm; margin-left:auto; text-align:center; line-height:1.1; margin-top:8mm; }
.signature-space { height:11mm; }
.two-signatures { width:100%; border-collapse:collapse; margin-top:8mm; }
.two-signatures td { width:50%; text-align:center; vertical-align:top; border:0; }
table.grid { width:100%; border-collapse:collapse; font-size:14pt; line-height:1.0; }
table.grid th, table.grid td { border:0.25mm solid #222; padding:1.2mm 1mm; vertical-align:middle; }
table.grid th { text-align:center; font-weight:bold; }
table.grid.small { font-size:12pt; }
table.grid.tiny { font-size:10.5pt; }
.no-border td,.no-border th { border:0 !important; }
.checkbox { display:inline-block; width:3.4mm; height:3.4mm; border:0.25mm solid #111; margin:0 1mm 0 2mm; vertical-align:middle; }
.section { font-weight:bold; margin-top:2mm; }
.blank-lines { line-height:1.45; }
.blank-line { border-bottom:0.2mm dotted #444; height:7mm; }
.garuda-letter { text-align:center; height:29mm; }
.garuda-letter img { width:24mm; }
.letter-top { width:100%; border-collapse:collapse; margin-top:-2mm; }
.letter-top td { width:50%; vertical-align:top; border:0; line-height:1.05; }
.attachments { width:100%; border-collapse:collapse; }
.attachments td { border:0; vertical-align:top; }
.photo-grid { width:100%; border-collapse:collapse; margin-top:3mm; }
.photo-grid td { width:50%; height:91mm; padding:3mm; text-align:center; vertical-align:middle; border:0; }
.photo-grid img { max-width:82mm; max-height:76mm; }
.photo-placeholder { border:0.3mm dashed #777; height:72mm; padding-top:30mm; color:#666; }
.certificate { text-align:center; }
.cert-frame { border:2mm double #c6a340; height:177mm; padding:10mm; }
.cert-org { font-size:25pt; font-weight:bold; margin-top:5mm; }
.cert-person { font-size:32pt; font-weight:bold; color:#3c2467; margin:8mm 0 3mm; }
.cert-text { font-size:22pt; line-height:1.35; }
.cert-garuda { width:25mm; }
.page-number { text-align:right; font-size:12pt; }
.open-memo { font-family:thsarabunnew135zws; font-size:16pt; line-height:1.01; color:#000; }
.open-top { width:100%; border-collapse:collapse; height:20mm; margin:0; }
.open-top td { width:33.33%; border:0; vertical-align:top; }
.open-top img { width:18mm; }
.open-code-cell { text-align:right; }
.open-code-box { display:inline-block; width:38mm; padding:3mm 2mm; border:.35mm solid #111; text-align:center; font-weight:bold; font-size:16pt; }
.open-title { margin:-11mm 0 2mm; text-align:center; font-size:25pt; line-height:1; }
.open-row { margin:0 0 1mm 0; white-space:nowrap; }
.open-label { font-weight:bold; }
.open-fill { display:inline-block; text-align:center; border-bottom:.25mm dotted #222; padding:0 .5mm; line-height:.93; vertical-align:baseline; }
.open-rule { border-bottom:.3mm solid #222; margin:.7mm 0 1.8mm; }
.open-body { line-height:1.18; }
.open-body p { margin:0 0 1.35mm; text-align:justify; }
.open-indent { text-indent:18mm; }
.open-budget { width:100%; border-collapse:collapse; margin:0 0 .5mm 18mm; font-size:15pt; line-height:1.05; }
.open-budget td { width:50%; border:0; padding:.55mm 9mm .55mm 0; white-space:nowrap; }
.open-sign { width:72mm; margin:16mm 8mm 0 auto; text-align:center; line-height:1.05; }
.open-sign-space { height:6mm; }
.invite-page { font-family:thsarabunnew135zws; font-size:16pt; line-height:1.12; color:#000; }
.invite-top { width:100%; border-collapse:collapse; margin:0; height:34mm; }
.invite-top td { width:33.33%; border:0; vertical-align:top; }
.invite-top img { width:25mm; }
.invite-code { width:36mm; margin-left:auto; border-collapse:collapse; }
.invite-code td { border:.35mm solid #111; padding:2.5mm 1mm; text-align:center; font-weight:bold; }
.invite-address { width:100%; border-collapse:collapse; margin-top:-5mm; }
.invite-address td { width:50%; border:0; vertical-align:top; }
.invite-address td:last-child { padding-left:18mm; }
.invite-meta { margin:0 0 2.2mm; }
.invite-field { display:inline-block; border-bottom:.25mm dotted #222; text-align:center; padding:0 .5mm; line-height:.95; }
.invite-body { margin-top:2.5mm; line-height:1.16; text-align:left; }
.invite-body p { margin:0 0 2.2mm; }
.invite-indent { text-indent:20mm; }
.invite-contact { margin-top:30mm; line-height:1.1; }
.reply-page { font-family:thsarabunnew135zws; font-size:14.5pt; line-height:1.03; color:#000; }
.reply-title { text-align:center; font-weight:bold; font-size:18pt; margin:0 0 1mm; }
.reply-center { text-align:center; margin:0 0 1mm; }
.reply-rule { border-bottom:.3mm solid #222; margin:3mm 0 5mm; }
.reply-row { margin:0 0 1.2mm; }
.reply-line { display:inline-block; border-bottom:.25mm dotted #222; min-width:45mm; padding:0 .5mm; line-height:.95; }
.reply-section { font-weight:bold; margin:2mm 0 1mm; }
.reply-sign { width:80mm; margin:10mm 5mm 0 auto; text-align:left; line-height:1.22; }
.reply-note { border-top:.3mm solid #222; margin-top:6mm; padding-top:5mm; line-height:1.16; }
CSS;
}

function pdf_form_code(string $code): string
{
    return '<table class="form-code-row"><tr><td></td><td class="form-code">แบบ '.pdf_h($code).'</td></tr></table>';
}

function pdf_memo_header(string $code = ''): string
{
    $codeCell = $code !== ''
        ? '<td class="memo-code-cell"><span class="form-code-inline">แบบ '.pdf_h($code).'</span></td>'
        : '<td></td>';

    return '<table class="memo-head"><tr><td class="memo-garuda"><img width="74" src="'.pdf_h(realpath(pdf_asset_path('images/garuda.png'))).'" /></td>'
        .'<td class="memo-title">บันทึกข้อความ</td>'.$codeCell.'</tr></table>';
}

function pdf_memo_meta(array $p, string $subject): string
{
    return '<div class="memo-row"><span class="memo-label">ส่วนราชการ</span> <span class="line-long">'.pdf_h(SENA_ORG['learning_center']).' &nbsp; '.pdf_h(SENA_ORG['district_short']).'</span> <span class="memo-label">โทรศัพท์</span> <span class="line">'.pdf_h(SENA_ORG['phone']).'</span></div>'.
        '<div class="memo-row"><span class="memo-label">ที่</span> '.pdf_h(SENA_ORG['official_no']).'/<span class="line" style="min-width:45mm">&nbsp;</span> <span class="memo-label">วันที่</span> <span class="line-long">'.pdf_h(pdf_thai_date(date('Y-m-d'))).'</span></div>'.
        '<div class="memo-row"><span class="memo-label">เรื่อง</span> '.pdf_h($subject).'</div><div class="rule"></div>'.
        '<div class="memo-row"><span class="memo-label">เรียน</span> ผู้อำนวยการ'.pdf_h(SENA_ORG['district_full']).'</div>';
}

function pdf_letter_header(array $p, string $subject, string $to, string $code = '', string $copy = ''): string
{
    $codeCell = $code !== ''
        ? '<td class="letter-code-cell"><span class="form-code-inline">แบบ '.pdf_h($code).'</span></td>'
        : '<td></td>';

    return ($copy ? '<div class="bold">'.pdf_h($copy).'</div>' : '').
        '<table class="letter-heading"><tr><td></td><td><img width="98" src="'.pdf_h(realpath(pdf_asset_path('images/garuda.png'))).'" /></td>'.$codeCell.'</tr></table>'.
        '<table class="letter-top"><tr><td>ที่ '.pdf_h(SENA_ORG['official_no']).'/........................</td><td>'.pdf_h(SENA_ORG['district_full']).'<br>'.pdf_h(SENA_ORG['address']).'</td></tr></table>'.
        '<p class="letter-date">'.pdf_h(pdf_thai_date(date('Y-m-d'))).'</p>'.
        '<p class="letter-field"><span class="bold">เรื่อง</span> '.pdf_h($subject).'</p><p class="letter-field"><span class="bold">เรียน</span> '.pdf_h($to).'</p>';
}

function pdf_external_signature_block(string $position = ''): string
{
    return '<div class="external-signature"><div>ขอแสดงความนับถือ</div><div class="external-signature-space"></div>'
        .'<div class="external-signature-identity"><div>( '.pdf_h(SENA_ORG['director_name']).' )</div>'
        .'<div class="external-signature-position">'.pdf_h($position ?: SENA_ORG['director_position']).'</div></div></div>';
}

function pdf_signature_block(string $position = ''): string
{
    return '<div class="signature"><div class="signature-space"></div><div>( '.pdf_h(SENA_ORG['director_name']).' )</div><div>'.pdf_h($position ?: SENA_ORG['director_position']).'</div></div>';
}

function pdf_responsible_signature(): string
{
    return '<div class="signature"><div>ลงชื่อ ........................................................</div><div>( '.pdf_h(SENA_ORG['responsible_name']).' )</div><div>'.pdf_h(SENA_ORG['responsible_position']).'</div></div>';
}

function pdf_org_footer(): string
{
    return '<div class="org-footer">'.pdf_h(SENA_ORG['owner_unit']).'<br>โทร. '.pdf_h(SENA_ORG['phone']).'<br>โทรสาร '.pdf_h(SENA_ORG['fax']).'</div>';
}
