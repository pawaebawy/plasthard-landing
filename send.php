<?php
/**
 * Приём заявок с лендинга PlastHard.
 * Письмо уходит на MAIL_TO. На хостинге From обычно должен быть ящиком этого же домена.
 */
declare(strict_types=1);

const MAIL_TO = 'info@plast-hard.ru';
const MAIL_FROM = 'info@plast-hard.ru';
const MAIL_FROM_NAME = 'PlastHard';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Метод не поддерживается']);
}

date_default_timezone_set('Europe/Moscow');

if (honeypot_filled()) {
    respond(200, ['ok' => true]);
}

if (!same_origin()) {
    respond(403, ['ok' => false, 'error' => 'Запрос отклонён']);
}

$form = field('form');
if (!in_array($form, ['lead', 'callback'], true)) {
    respond(422, ['ok' => false, 'error' => 'Неизвестная форма']);
}

$name = single_line(field('name'), 120);
$phone = single_line(field('phone'), 32);
$email = single_line(field('email'), 160);
$comment = text_block(field('comment'), 2000);
$product = single_line(field('product'), 160);
$consent = field('consent');

if (text_length($name) < 2) {
    respond(422, ['ok' => false, 'error' => 'Укажите имя']);
}

$digits = preg_replace('/\D+/', '', $phone) ?? '';
if (strlen($digits) !== 11 || $digits[0] !== '7') {
    respond(422, ['ok' => false, 'error' => 'Укажите телефон']);
}

if ($form === 'lead' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'error' => 'Укажите e-mail']);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = '';
}

if ($consent !== '1' && $consent !== 'on') {
    respond(422, ['ok' => false, 'error' => 'Нужно согласие на обработку данных']);
}

$isLead = $form === 'lead';
$subject = $isLead
    ? 'Заявка на сотрудничество — PlastHard'
    : ($product !== '' ? 'Заявка: ' . $product . ' — PlastHard' : 'Заказ звонка — PlastHard');

$lines = [
    $isLead ? 'Новая заявка на сотрудничество' : 'Новая заявка на звонок',
    '',
    'Имя: ' . $name,
    'Телефон: ' . $phone,
];

if ($email !== '') {
    $lines[] = 'E-mail: ' . $email;
}
if ($product !== '') {
    $lines[] = 'Продукт: ' . $product;
}
if ($comment !== '') {
    $lines[] = '';
    $lines[] = 'Комментарий:';
    $lines[] = $comment;
}

$lines[] = '';
$lines[] = 'Согласие на обработку персональных данных: да';
$lines[] = 'Время: ' . date('d.m.Y H:i');

$body = implode("\n", $lines);

$fromName = encode_header(MAIL_FROM_NAME);
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: ' . $fromName . ' <' . MAIL_FROM . '>',
];

if ($email !== '') {
    $headers[] = 'Reply-To: ' . $email;
}

$sent = mail(
    MAIL_TO,
    encode_header($subject),
    $body,
    implode("\r\n", $headers),
    '-f' . MAIL_FROM
);

if (!$sent) {
    respond(500, ['ok' => false, 'error' => 'Не удалось отправить заявку']);
}

respond(200, ['ok' => true]);

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function field(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? $value : '';
}

function honeypot_filled(): bool
{
    $value = field('ph_check');
    return trim($value) !== '';
}

function same_origin(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return false;
    }

    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if ($source === '') {
        return true;
    }

    $sourceHost = parse_url($source, PHP_URL_HOST);
    if (!is_string($sourceHost) || $sourceHost === '') {
        return false;
    }

    return strcasecmp($sourceHost, preg_replace('/:\d+$/', '', $host) ?? $host) === 0;
}

function single_line(string $value, int $max): string
{
    $value = trim(preg_replace('/[\r\n]+/u', ' ', $value) ?? '');
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return limit_text($value, $max);
}

function text_block(string $value, int $max): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = trim($value);
    return limit_text($value, $max);
}

function limit_text(string $value, int $max): string
{
    if (text_length($value) <= $max) {
        return $value;
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }

    return substr($value, 0, $max);
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function encode_header(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}
