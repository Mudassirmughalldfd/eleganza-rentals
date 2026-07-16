<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="eleganza-enquiries-'.date('Y-m-d').'.csv"');
$out = fopen('php://output', 'wb');
fputcsv($out, ['Date','Request Type','Subject','Name','Email','Phone','Preferred Contact','Vehicle','Start Date','End Date','Status','Message','Admin Notes','Mail Status']);
foreach (enquiries_all() as $item) {
    fputcsv($out, [
        $item['created_at'] ?? '',
        ($item['source'] ?? '') === 'book_now' ? 'Booking request' : 'Contact message',
        $item['subject'] ?? '', $item['name'] ?? '', $item['email'] ?? '', $item['phone'] ?? '',
        $item['contact_method'] ?? '', $item['vehicle_name'] ?? '', $item['start_date'] ?? '', $item['end_date'] ?? '',
        $item['status'] ?? '', $item['message'] ?? '', $item['admin_notes'] ?? '', $item['mail_status'] ?? '',
    ]);
}
fclose($out);
exit;
