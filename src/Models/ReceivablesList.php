<?php
namespace Pdik\src\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pdik\src\Services\Exact;

class ReceivablesList extends Model
{
    protected $table = 'receivables';
    use HasFactory;
    protected $fillable = [
        'HID','AccountCode','AccountId','AccountName','Amount','AmountInTransit',
        'CurrencyCode','Description','DueDate','EntryNumber','receivable_id','InvoiceDate','InvoiceNumber',
        'JournalCode','JournalDescription','YourRef'
    ];
    protected $dates = ['InvoiceDate','DueDate'];

     public static function ExactUpdate($receivable){
          $new=  ReceivablesList::firstOrCreate(
            ['HID' => $receivable->HID],
              [
                  'HID' => $receivable->HID,
                  'AccountCode' => $receivable->AccountCode,
                  'AccountId' => $receivable->AccountId,
                  'AccountName' => $receivable->AccountName,
                  'Amount' => $receivable->Amount,
                  'AmountInTransit' => $receivable->AmountInTransit,
                  'CurrencyCode' => $receivable->CurrencyCode,
                  'Description' => $receivable->Description,
                  'DueDate' => Exact::toDateTime($receivable->DueDate),
                  'EntryNumber' => $receivable->EntryNumber,
                  'receivable_id' => $receivable->id,
                  'InvoiceDate' =>  Exact::toDateTime($receivable->InvoiceDate),
                  'InvoiceNumber' => $receivable->InvoiceNumber,
                  'JournalCode' => $receivable->JournalCode,
                  'JournalDescription' => $receivable->JournalDescription,
                  'YourRef' => $receivable->YourRef,
              ]);

     }

}
