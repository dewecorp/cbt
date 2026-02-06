<?php
/**
 * Helper function to parse DOCX file for Question Import
 * Extracts text from word/document.xml
 */

function readDocx($filename) {
    $content = '';
    $zip = new ZipArchive;
    
    if ($zip->open($filename) === TRUE) {
        // Check if word/document.xml exists
        if (($index = $zip->locateName('word/document.xml')) !== false) {
            $xml_data = $zip->getFromIndex($index);
            $zip->close();
            
            $dom = new DOMDocument;
            $dom->loadXML($xml_data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            
            $xmlPath = new DOMXPath($dom);
            $xmlPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            
            $paragraphs = $xmlPath->query('//w:p');
            
            $text_content = [];
            
            foreach ($paragraphs as $p) {
                $text = '';
                $runs = $xmlPath->query('w:r/w:t', $p);
                foreach ($runs as $run) {
                    $text .= $run->nodeValue;
                }
                if(trim($text) != '') {
                    $text_content[] = trim($text);
                }
            }
            return $text_content;
        }
        $zip->close();
    }
    return false;
}

function parseQuestionsFromDocx($filename) {
    $lines = readDocx($filename);
    if ($lines === false) return false;

    $questions = [];
    $q_temp = [
        'jenis' => 'pilihan_ganda', // Default, will change logic
        'pertanyaan' => '',
        'opsi_a' => '',
        'opsi_b' => '',
        'opsi_c' => '',
        'opsi_d' => '',
        'opsi_e' => '',
        'kunci' => '',
        'kiri' => [], // For menjodohkan
        'kanan' => [] // For menjodohkan
    ];

    $collecting_question = false;
    
    // Regex patterns
    $pattern_option = '/^([A-E])\./i'; // Matches A. B. C. D. E.
    $pattern_key = '/^(Kunci|Jawaban)\s*:\s*(.*)/i'; // Matches Kunci: ...
    $pattern_jenis = '/^Jenis\s*:\s*(.*)/i'; // Matches Jenis: ...
    $pattern_match = '/(.*)\s*=>\s*(.*)/'; // Matches Left => Right for Menjodohkan

    $saveQuestion = function($q) use (&$questions) {
        if (!empty($q['pertanyaan'])) {
            // Determine Type Logic if not explicit
            if ($q['jenis'] == 'pilihan_ganda') { // Default value check
                if (!empty($q['kiri'])) {
                    $q['jenis'] = 'menjodohkan';
                    // Convert match arrays to JSON strings for compatibility
                    // (Actually we return raw arrays here and handle in buat_soal.php)
                } elseif (strpos($q['kunci'], ',') !== false && !empty($q['opsi_a'])) {
                    $q['jenis'] = 'pilihan_ganda_kompleks';
                } elseif (empty($q['opsi_a']) && !empty($q['kunci'])) {
                     // Check length of key or explicit marking
                     if (strlen($q['kunci']) > 50) {
                         $q['jenis'] = 'essay';
                     } else {
                         $q['jenis'] = 'isian_singkat';
                     }
                }
            }
            
            // Clean up arrays if not needed
            if ($q['jenis'] != 'menjodohkan') {
                unset($q['kiri']);
                unset($q['kanan']);
            }
            
            $questions[] = $q;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Check for Explicit Type
        if (preg_match($pattern_jenis, $line, $matches)) {
            if ($collecting_question) {
                $type = strtolower(trim($matches[1]));
                if (strpos($type, 'essay') !== false || strpos($type, 'uraian') !== false) $q_temp['jenis'] = 'essay';
                elseif (strpos($type, 'isian') !== false) $q_temp['jenis'] = 'isian_singkat';
                elseif (strpos($type, 'jodoh') !== false) $q_temp['jenis'] = 'menjodohkan';
                elseif (strpos($type, 'kompleks') !== false) $q_temp['jenis'] = 'pilihan_ganda_kompleks';
                elseif (strpos($type, 'ganda') !== false) $q_temp['jenis'] = 'pilihan_ganda';
            }
            continue;
        }

        // Check for Key
        if (preg_match($pattern_key, $line, $matches)) {
            if ($collecting_question) {
                $q_temp['kunci'] = trim($matches[2]);
                $saveQuestion($q_temp);
                
                // Reset
                $q_temp = [
                    'jenis' => 'pilihan_ganda',
                    'pertanyaan' => '',
                    'opsi_a' => '', 'opsi_b' => '', 'opsi_c' => '', 'opsi_d' => '', 'opsi_e' => '',
                    'kunci' => '',
                    'kiri' => [], 'kanan' => []
                ];
                $collecting_question = false;
            }
            continue;
        }

        // Check for Match Pattern (Menjodohkan)
        if (strpos($line, '=>') !== false) {
             if ($collecting_question) {
                 $parts = explode('=>', $line);
                 if (count($parts) == 2) {
                     $q_temp['kiri'][] = trim($parts[0]);
                     $q_temp['kanan'][] = trim($parts[1]);
                     continue;
                 }
             }
        }

        // Check for Options
        if (preg_match($pattern_option, $line, $matches)) {
            $opt_label = strtoupper($matches[1]);
            $opt_text = trim(substr($line, 2));
            
            if ($opt_label == 'A') $q_temp['opsi_a'] = $opt_text;
            if ($opt_label == 'B') $q_temp['opsi_b'] = $opt_text;
            if ($opt_label == 'C') $q_temp['opsi_c'] = $opt_text;
            if ($opt_label == 'D') $q_temp['opsi_d'] = $opt_text;
            if ($opt_label == 'E') $q_temp['opsi_e'] = $opt_text;
            
            continue;
        }

        // Check for New Question Start (Number followed by dot)
        if (preg_match('/^\d+\./', $line)) {
            // If previous question unfinished (no key found), save it?
            if ($collecting_question && !empty($q_temp['pertanyaan'])) {
                $saveQuestion($q_temp);
                $q_temp = [
                    'jenis' => 'pilihan_ganda',
                    'pertanyaan' => '',
                    'opsi_a' => '', 'opsi_b' => '', 'opsi_c' => '', 'opsi_d' => '', 'opsi_e' => '',
                    'kunci' => '',
                    'kiri' => [], 'kanan' => []
                ];
            }
            $collecting_question = true;
            $q_temp['pertanyaan'] = trim(preg_replace('/^\d+\./', '', $line));
        } else {
            // Append to question text if collecting and not an option/match
            if ($collecting_question && empty($q_temp['opsi_a']) && empty($q_temp['kiri'])) {
                $q_temp['pertanyaan'] .= ' ' . $line;
            }
        }
    }
    
    // Add last question
    if ($collecting_question && !empty($q_temp['pertanyaan'])) {
        $saveQuestion($q_temp);
    }

    return $questions;
}
?>