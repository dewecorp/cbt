<?php
/**
 * Script to generate a simple DOCX file on the fly without external libraries.
 * Generates 'template_soal_cbt.docx'
 */

// Function to create a simple DOCX
function generateDocx($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die("Cannot open <$filename>\n");
    }

    // 1. [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // 2. _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // 3. word/_rels/document.xml.rels
    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>';
    // We don't strictly need this if we have no images/hyperlinks, but good practice.
    // Actually, let's skip it for simplicity unless required. Word usually opens without it if no rels used.
    // But let's add an empty one to be safe.
    $zip->addEmptyDir('word/_rels');
    $zip->addFromString('word/_rels/document.xml.rels', $docRels);

    // 4. word/document.xml - The Content
    // We construct the XML content manually.
    $xmlContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>';

    $lines = [
        "TEMPLATE IMPORT SOAL CBT",
        "Panduan Format:",
        "1. Nomor soal (1., 2., dst) menandakan awal soal.",
        "2. Opsi jawaban (A., B., dst) untuk Pilihan Ganda.",
        "3. 'Kunci: ...' di akhir soal.",
        "4. 'Jenis: ...' (Opsional) untuk memaksa jenis soal (Essay, Isian).",
        "",
        "--- CONTOH SOAL ---",
        "",
        "1. Ini adalah contoh soal Pilihan Ganda Biasa. Apa warna langit?",
        "A. Merah",
        "B. Biru",
        "C. Hijau",
        "D. Kuning",
        "E. Ungu",
        "Kunci: B",
        "",
        "2. Ini adalah contoh soal Pilihan Ganda Kompleks (Jawaban lebih dari satu). Manakah yang termasuk buah?",
        "A. Apel",
        "B. Kucing",
        "C. Mangga",
        "D. Meja",
        "E. Pisang",
        "Kunci: A, C, E",
        "",
        "3. Ini adalah contoh soal Menjodohkan. Pasangkan ibukota berikut:",
        "Indonesia => Jakarta",
        "Jepang => Tokyo",
        "Inggris => London",
        "Kunci: MATCH",
        "",
        "4. Ini adalah contoh soal Isian Singkat. Siapa presiden pertama RI?",
        "Kunci: Soekarno",
        "",
        "5. Ini adalah contoh soal Uraian / Essay. Jelaskan proses terjadinya hujan!",
        "Jenis: Essay",
        "Kunci: Air laut menguap, membentuk awan, lalu turun hujan."
    ];

    foreach ($lines as $line) {
        $xmlContent .= '<w:p><w:r><w:t>' . htmlspecialchars($line) . '</w:t></w:r></w:p>';
    }

    $xmlContent .= '</w:body></w:document>';
    $zip->addFromString('word/document.xml', $xmlContent);

    $zip->close();
}

// Generate and Serve
$filename = 'template_soal_word.docx';
generateDocx($filename);

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="'.basename($filename).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filename));
readfile($filename);
unlink($filename); // Clean up
exit;
?>