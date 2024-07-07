<?php

namespace Idoalit\S2i\Libs;

use Idoalit\S2i\Libs\CatalogHelper;
use Idoalit\S2i\Libs\Helper;
use Idoalit\S2i\Models\Catalog;
use Idoalit\S2i\Models\Collection;
use Idoalit\S2i\Models\Worksheet;
use Idoalit\SlimsEloquentModels\Biblio;
use Idoalit\SlimsEloquentModels\Item;

class IndexHelper
{
    public static function run(Biblio $biblio) : Biblio
    {
        // get biblio data
        $SLiMS_BIBID = Helper::getSLiMSBIBID($biblio->biblio_id);
        $authors = Helper::getAuthors($biblio->biblio_id);
        $topics = Helper::getTopics($biblio->biblio_id);

        // get worksheet of Monograf
        $worksheet = Worksheet::where('Name', 'Monograf')->first();

        $catalogHelper = new CatalogHelper($worksheet, $biblio);
        $catalog = $catalogHelper->updateOrCreate($SLiMS_BIBID, $authors, $topics);
        self::sendItem($biblio, $catalog);

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

        return $biblio;
    }

    public static function sendItem(Biblio $biblio, Catalog $catalog) {
        foreach ($biblio->items as $item) {
            $criteria = ['NomorBarcode' => $item->item_code];
            $value = [
                'NoInduk' => $item->inventory_code,
                'Currency' => $item->price_currency == 'Rupiah' ? 'IDR' : $item->price_currency,
                'Price' => $item->price,
                'PriceType' => 'Per eksemplar',
                'TanggalPengadaan' => $item->order_date,
                'CallNumber' => $item->call_number ?? $biblio->call_number,
                'Catalog_id' => $catalog->ID,
                'Status_id' => in_array($item->item_status_id, [0, '0', null, '']) ? 1 : null,
                'CreateTerminal' => 'SLiMS',
                'UpdateTerminal' => 'SLiMS',
            ];
            
            try {
                Collection::updateOrCreate($criteria, $value);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }
}
