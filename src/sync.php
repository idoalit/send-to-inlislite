<?php

use Idoalit\S2i\Libs\CatalogHelper;
use Idoalit\S2i\Libs\Helper;
use Idoalit\S2i\Models\Worksheet;
use Idoalit\SlimsEloquentModels\Biblio;

// get biblio data
$biblio = Biblio::find($_GET['id'] ?? 1);
$SLiMS_BIBID = 'SLiMS' . str_pad($biblio->biblio_id, 10, '0', STR_PAD_LEFT);
$authors = Helper::getAuthors($biblio->biblio_id);
$topics = Helper::getTopics($biblio->biblio_id);

// get worksheet of Monograf
$worksheet = Worksheet::where('Name', 'Monograf')->first();

$catalogHelper = new CatalogHelper($worksheet, $biblio);
$catalog = $catalogHelper->updateOrCreate($SLiMS_BIBID, $authors, $topics);

// 	001	Nomor Kendali
$catalogHelper->updateOrCreateRuas('001', $catalog->ControlNumber, null, null);

// 	005	Tanggal Dan Jam Pemakaian Terakhir
$catalogHelper->updateOrCreateRuas('005', date('YmdHis'), null, null);

// 	007	Ruas Tetap Deskripsi Fisik (Keterangan Umum)
$catalogHelper->updateOrCreateRuas('007', 'ta', null, null);

// 	008	Unsur Data Yang Panjangnya Tetap
$catalogHelper->updateOrCreateRuas('008', date('ymd') . '###########################0#' . str_pad($biblio->language_id, 5, '#'), null, null);

// 	020	ISBN
$catalogHelper->updateOrCreateRuas('020', '$a ' . $catalog->ISBN);

// 	035	BIB-ID (Nomor kendali dari sistem lain)
$catalogHelper->updateOrCreateRuas('035', '$a ' . $catalog->BIBID, '#', '#', true);

// 	035	BIB-ID (Nomor kendali dari sistem lain)
$catalogHelper->updateOrCreateRuas('035', '$a ' . $SLiMS_BIBID, '#', '#', true);

// 	082	Nomor Panggil Desimal Dewey (DDC)
$catalogHelper->updateOrCreateRuas('082', '$a ' . $catalog->DeweyNo);

// 	084	Nomor Klasifikasi Lainnya
$catalogHelper->updateOrCreateRuas('084', '$a ' . $catalog->CallNumber);

// 	100	Entri Utama -- Nama Orang
$catalogHelper->updateOrCreateRuas('100', '$a ' . $authors[0]->author_name);

// 	245	Pernyataan Judul
$title = str_replace(':', ': $b', $biblio->title);
$title .= '/$c ' . $biblio->sor;
$catalogHelper->updateOrCreateRuas('245', '$a ' . $title, 1);

// 	250	Pernyataan Edisi
$catalogHelper->updateOrCreateRuas('250', '$a ' . $catalog->Edition);

// 	260	Penerbitan (Impresum)
$catalogHelper->updateOrCreateRuas('260', '$a ' . $catalog->PublishLocation . ' :$b ' . $catalog->Publisher . ',$c ' . $catalog->PublishYear);

// 	300	Deskripsi Fisik
$collation = str_replace(':', ': $b', $catalog->PhysicalDescription);
$collation = str_replace(';', '; $c', $collation);
$catalogHelper->updateOrCreateRuas('300', '$a ' . $collation);

// 	440	Pernyataan Seri/Entri Tambahan - Judul
$catalogHelper->updateOrCreateRuas('440', '$a ' . $biblio->series_title);

// 	490	Pernyataan Seri
$catalogHelper->updateOrCreateRuas('490', '$a ' . $biblio->series_title);

//  520	Catatan Ringkasan, Isi, dsb	
$catalogHelper->updateOrCreateRuas('520', '$a ' . $catalog->Note);

// process topics
foreach ($topics as $topic) {
    switch ($topic->topic_type) {
        case 'g':
            // 	651	Entri Tambahan Subjek-Nama Geografis
            $catalogHelper->updateOrCreateRuas('651', '$a ' . $topic->topic, '#', '4', true);
            break;

        case 'n':
            // 	600	Entri Tambahan Subjek-Nama orang
            $catalogHelper->updateOrCreateRuas('600', '$a ' . $topic->topic, '#', '4', true);
            break;

        case 'tm':
            $catalogHelper->updateOrCreateRuas('650', '$a ' . $topic->topic, '#', '4', true);
            break;

        case 'gr':
            $catalogHelper->updateOrCreateRuas('650', '$a ' . $topic->topic, '#', '4', true);
            break;

        case 'oc':
            $catalogHelper->updateOrCreateRuas('650', '$a ' . $topic->topic, '#', '4', true);
            break;

        case 't':
        default:
            // 	650	Entri Tambahan Subjek-Topik
            $catalogHelper->updateOrCreateRuas('650', '$a ' . $topic->topic, '#', '4', true);
            break;
    }
}

// process authors
foreach ($authors as $author) {
    switch ($author->authority_type) {
        case 'o':
            // 	710	Entri Tambahan-Nama Badan Korporasi
            $catalogHelper->updateOrCreateRuas('710', '$a ' . $author->author_name, '#', '4', true);
            break;

        case 'c':
            // 	711	Entri Tambahan - Nama Pertemuan
            $catalogHelper->updateOrCreateRuas('711', '$a ' . $author->author_name, '#', '4', true);
            break;

        default:
            // 	700	Entri Tambahan-Nama Orang
            $catalogHelper->updateOrCreateRuas('700', '$a ' . $author->author_name, '#', '4', true);
            break;
    }
}

exit;
