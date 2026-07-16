<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    verify_csrf();
    if (!empty($_POST['website'])) {
        echo json_encode(['ok' => true, 'message' => 'Thank you.']);
        exit;
    }

    $last = (int) ($_SESSION['last_enquiry_at'] ?? 0);
    if (time() - $last < 20) {
        throw new RuntimeException('Please wait a moment before sending another request.');
    }

    $source = trim((string) ($_POST['source'] ?? 'website'));
    $isBooking = $source === 'book_now';
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $contactMethod = trim((string) ($_POST['contact_method'] ?? ''));

    if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) < 7) {
        throw new RuntimeException('Please complete your name, email address and phone number correctly.');
    }
    if (empty($_POST['consent'])) {
        throw new RuntimeException('Please agree to the consent statement.');
    }

    $vehicleId = trim((string) ($_POST['vehicle_id'] ?? ''));
    $vehicle = $vehicleId ? car_by_id($vehicleId) : null;
    $start = trim((string) ($_POST['start_date'] ?? ''));
    $end = trim((string) ($_POST['end_date'] ?? ''));

    if ($isBooking) {
        if (!$vehicle) {
            throw new RuntimeException('Please select a vehicle.');
        }
        if ($start === '' || $end === '') {
            throw new RuntimeException('Please select both your preferred start and end dates.');
        }

        $timezone = new DateTimeZone((string) (cfg('timezone') ?: 'Europe/London'));
        $selectedStart = DateTimeImmutable::createFromFormat('!Y-m-d', $start, $timezone);
        $selectedEnd = DateTimeImmutable::createFromFormat('!Y-m-d', $end, $timezone);
        $today = new DateTimeImmutable('today', $timezone);
        if (!$selectedStart || !$selectedEnd) {
            throw new RuntimeException('Please select valid booking dates.');
        }
        if ($selectedStart < $today) {
            throw new RuntimeException('The start date cannot be in the past.');
        }
        if ($selectedEnd < $selectedStart) {
            throw new RuntimeException('The end date must be the same as or after the start date.');
        }

        $selectedEndExclusive = $selectedEnd->modify('+1 day');
        foreach (availability_for_car((string) $vehicle['id']) as $block) {
            try {
                $blockStart = new DateTimeImmutable((string) ($block['start_at'] ?? ''), $timezone);
                $blockEnd = new DateTimeImmutable((string) ($block['end_at'] ?? ''), $timezone);
            } catch (Throwable) {
                continue;
            }
            if ($selectedStart < $blockEnd && $selectedEndExclusive > $blockStart) {
                $label = status_label((string) ($block['status'] ?? 'unavailable'));
                $returnText = !empty($block['show_return'])
                    ? ' It is expected back ' . format_date_time((string) ($block['end_at'] ?? '')) . '.'
                    : '';
                throw new RuntimeException($vehicle['make'].' '.$vehicle['model'].' is unavailable for the selected dates ('.$label.').'.$returnText.' Please choose different dates.');
            }
        }

        $status = car_status($vehicle);
        if (!$status['available'] && empty(availability_for_car((string) $vehicle['id']))) {
            throw new RuntimeException($vehicle['make'].' '.$vehicle['model'].' is not currently accepting booking requests. Please choose another vehicle or contact us.');
        }

        if ($message === '') {
            $message = 'Booking request submitted for '.$start.' to '.$end.'.';
        }
        $subject = 'Vehicle booking request';
    } else {
        if ($subject === '') {
            throw new RuntimeException('Please select an enquiry type.');
        }
        if (strlen($message) < 5) {
            throw new RuntimeException('Please enter your message.');
        }
        $vehicleId = '';
        $vehicle = null;
        $start = '';
        $end = '';
    }

    $enquiry = save_enquiry([
        'id' => '',
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'contact_method' => $contactMethod,
        'vehicle_id' => $vehicleId,
        'vehicle_name' => $vehicle ? ($vehicle['make'].' '.$vehicle['model']) : 'General enquiry',
        'start_date' => $start,
        'end_date' => $end,
        'message' => $message,
        'source' => $source,
        'status' => 'new',
        'admin_notes' => '',
        'mail_status' => 'pending',
        'created_at' => '',
    ]);

    $mail = send_enquiry_emails($enquiry);
    $enquiry['mail_status'] = $mail['sent'] ? 'sent' : 'not_sent: '.$mail['message'];
    save_enquiry($enquiry);
    activity($isBooking ? 'New booking request' : 'New contact message', $name.' — '.$enquiry['vehicle_name']);
    $_SESSION['last_enquiry_at'] = time();

    echo json_encode([
        'ok' => true,
        'message' => $isBooking
            ? 'Thank you. Your booking request has been received. Eleganza Rentals will confirm the vehicle and dates with you shortly.'
            : 'Thank you. Your message has been received and Eleganza Rentals will contact you shortly.'
    ]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
