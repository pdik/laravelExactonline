<?php
namespace Pdik\src\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pdik\src\Services\Exact;

class TransactionLines extends Model
{
    protected $table = 'transaction_lines';
    use HasFactory;
    public static function ExactUpdate($transaction){
          $new=  TransactionLines::firstOrCreate(
            ['transaction_id' => $transaction->ID],
              [
                  'transaction_id'   => $transaction->transaction_id,
                  'Account'          => $transaction->Account,
                  'AccountCode'      => $transaction->AccountCode,
                  'AccountName'      => $transaction->AccountName,
                  'AmountDC'         => $transaction->AmountDC,
                  'AmountFC'         => $transaction->AmountFC,
                  'AmountVATBaseFC'  => $transaction->AmountVATBaseFC,
                  'AmountVATFC'      => $transaction->AmountVATFC,

                  'Asset'            => $transaction->Asset,
                  'AssetCode'        => $transaction->AssetCode,
                  'AssetDescription' => $transaction->AssetDescription,

                  'CostCenter'       => $transaction->CostCenter,
                  'CostCenterDescription'=> $transaction->CostCenterDescription,
                  'CostUnit'             => $transaction->CostUnit,
                  'CostUnitDescription'  => $transaction->CostUnitDescription,

                  'Created'              => Exact::toDateTime($transaction->Created),
                  'Creator'              => $transaction->CreatorFullName,
                  'Currency'             => $transaction->Currency,
                  'Date'                 => Exact::toDateTime($transaction->Date),
                  'Description'          => $transaction->Description,
                  'Division'             => $transaction->Division,

                  'Document'             => $transaction->Document,
                  'DocumentNumber'       => $transaction->DocumentNumber,
                  'DocumentSubject'      => $transaction->DocumentSubject,
                  'DueDate'              => Exact::toDateTime($transaction->DueDate),

                  'EntryID'              => $transaction->EntryID,
                  'EntryNumber'          => $transaction->EntryNumber,

                  'ExchangeRate'         => $transaction->ExchangeRate,
                  'ExtraDutyAmountFC'    => $transaction->ExtraDutyAmountFC,
                  'ExtraDutyPercentage'  => $transaction->ExtraDutyPercentage,

                  'FinancialPeriod'      => $transaction->FinancialPeriod,
                  'FinancialYear'        => $transaction->FinancialYear,

                  'GLAccount'            => $transaction->GLAccount,
                  'GLAccountCode'        => $transaction->GLAccountCode,
                  'GLAccountDescription' => $transaction->GLAccountDescription,

                  'InvoiceNumber'        => $transaction->InvoiceNumber,

                  'item'                 => $transaction->item,
                  'ItemCode'             => $transaction->ItemCode,
                  'ItemDescription'      => $transaction->ItemDescription,

                  'JournalCode'          => $transaction->JournalCode,
                  'JournalDescription'   => $transaction->JournalDescription,

                  'LineNumber'           => $transaction->LineNumber,
                  'LineType'             => $transaction->LineType,

                  'Modified'             => $transaction->Modified,
                  'Modifier'             => $transaction->Modifier,
                  'ModifierFullName'     => $transaction->ModifierFullName,
                  'Notes'                => $transaction->Notes,
                  'OffsetID'             => $transaction->OffsetID,
                  'OrderNumber'          => $transaction->OrderNumber,

                  'PaymentDiscountAmount'=> $transaction->PaymentDiscountAmount,
                  'PaymentReference'     => $transaction->PaymentReference,

                  'project'              => $transaction->project,
                  'ProjectCode'          => $transaction->ProjectCode,
                  'ProjectDescription'   => $transaction->ProjectDescription,

                  'Quantity'             => $transaction->Quantity,
                  'SerialNumber'         => $transaction->SerialNumber,
                  'ShopOrder'            => $transaction->ShopOrder,
                  'Status'               => $transaction->Status,

                  'Subscription'         => $transaction->Subscription,
                  'SubscriptionDescription' => $transaction->SubscriptionDescription,

                  'TrackingNumber'       => $transaction->TrackingNumber,
                  'TrackingNumberDescription'=> $transaction->TrackingNumberDescription,
                  'type'                 => $transaction->type,

                  'VATCode'              => $transaction->VATCode,
                  'VATCodeDescription'   => $transaction->VATCodeDescription,
                  'VATPercentage'        => $transaction->VATPercentage,
                  'VATType'              => $transaction->VATType,
                  'YourRef'              => $transaction->YourRef,
              ]);

     }
      public function customer(){
        $this->belongsTo(Customer::class,'Exact_id','AccountId');
    }
}
