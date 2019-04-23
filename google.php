<?php
include 'includes/php_query.php';
set_time_limit(0);

$result_array = csv2array('products.csv');

//Глубина парсинга. Число кратное 20.
$count = 1;


foreach ($result_array as &$item) {
    $temp_item = array();

    $item[1] = windows2utf(trim($item[1]));

    $html = google($item[1]);

    $pq = phpQuery::newDocument($html);
    $images = $pq->find(".images_table img']");
    $temp_item['name'] = $item[1];
    $temp_item['url'] = $images[0]->attr('src');
    $item[5] = $temp_item;
}

foreach ($result_array as &$item) {
    $content = file_get_contents($item[5]['url']);
    $item[5]['name'] = utf2windows($item[5]['name']);
    file_put_contents('google/' . $item[5]['name'] . '.jpg', $content);
    $item[5] = $item[5]['name'] . '.jpg';
}


download_send_headers("data_export_" . date("Y-m-d") . ".csv");
echo array2csv($result_array);
exit;
flush();


/**
 * Транслитерация
 * @param $string - Строка на кирилице
 * @return string - Строка для латинице
 */
function rus2translit($string)
{
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

        'А' => 'A', 'Б' => 'B', 'В' => 'V',
        'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
        'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
        'И' => 'I', 'Й' => 'Y', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R',
        'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
        'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    );
    return strtr($string, $converter);
}


function get_web_page($url)
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING => "",       // handle all encodings
        CURLOPT_USERAGENT => "spider", // who am i
        CURLOPT_AUTOREFERER => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT => 120,      // timeout on response
        CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;
    return $content;
}


function google($query)
{
    $query = urlencode($query);
    $url = "https://www.google.com/search?q=$query&newwindow=1&rlz=1C1PRFI_enRU836RU836&tbm=isch&source=lnt&tbs=ic:trans&sa=X&ved=0ahUKEwinvMT79ebhAhWKIZoKHXqKAfYQpwUIIA&biw=1920&bih=888&dpr=1";


    return get_web_page($url);
}


function array2csv(array &$array)
{

    if (count($array) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
//   fputcsv($df, array_keys(reset($array)), ';');
    foreach ($array as $row) {
        $row[1] = utf2windows($row[1]);
        fputcsv($df, $row, ';');
    }
    fclose($df);
    return ob_get_clean();
}


function csv2array($fileName)
{
    $csvData = file_get_contents($fileName);
    $lines = explode("\n", $csvData);
    $array = array();
    foreach ($lines as $line) {
        $temp = str_getcsv($line, ';');
        if (!empty($temp[1])) {
            $array[] = $temp;
        }
    }

    return $array;
}

function windows2utf($str)
{
    $encoding_from = 'windows-1251';
    $encoding_to = 'utf-8';
    return mb_convert_encoding($str, $encoding_to, $encoding_from);
}

function utf2windows($str)
{
    $encoding_from = 'utf-8';
    $encoding_to = 'windows-1251';
    return mb_convert_encoding($str, $encoding_to, $encoding_from);
}

function download_send_headers($filename)
{
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}