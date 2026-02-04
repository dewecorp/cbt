<?php
require '../../vendor/autoload.php';

use Shuchkin\SimpleXLSXGen;

$data = [
    ['Jenis Soal', 'Pertanyaan', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci Jawaban'],
    ['pilihan_ganda', 'Contoh Pertanyaan PG', 'Jawaban A', 'Jawaban B', 'Jawaban C', 'Jawaban D', 'Jawaban E', 'A'],
    ['pilihan_ganda_kompleks', 'Contoh Pertanyaan PGK', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'A,C'],
    ['isian_singkat', 'Ibukota Indonesia adalah...', '', '', '', '', '', 'Jakarta'],
    ['essay', 'Jelaskan tentang...', '', '', '', '', '', 'Kata Kunci'],
];

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs('template_soal.xlsx');
