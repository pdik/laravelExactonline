<?php
namespace Pdik\laravelExactonline\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pdik\laravelExactonline\Services\Exact;

class ExactSalesInvoices extends Model
{
    protected $table = 'invoices';
    protected $fillable = [
        'invoice_id','amount_dc','amount_discount','amount_discount_exl_vat',
        'amount_fc','amount_fc_exl_vat','created','paid_status','creator','creator_full_name',
        'currency','deliver_to','deliver_to_address','deliver_to_contact_person',
        'deliver_to_contact_person_full_name',
        'deliver_to_name', 'description','discount','discount_type',
        'division','document','document_number',
        'document_subject','due_date','extra_duty_amount_fc','ga_account_amount_fc','invoice_date',
        'invoice_number','invoice_to','invoice_to_contact_person','invoice_to_contact_person_full_name',
        'invoice_to_name','is_extra_duty','journal','journal_description','modified','modifier',
        'modifier_full_name','order_date','ordered_by','ordered_by_contact_person',
        'ordered_by_contact_person_full_name','ordered_by_name',
        'order_number','payment_condition','payment_condition_description',
        'payment_reference','remarks','sales_person','sales_person_full_name',
        'selection_code','selection_code_description','starter_sales_invoice_status',
        'starter_sales_invoice_status_description','status','status_description',
        'tax_schedule','tax_schedule_code','tax_schedule_description','type',
        'type_description','vat_amount_dc','vat_amount_fc','warehouse','with_holding_tax_amount_fc',
        'with_holding_tax_base_amount','with_holding_tax_percentage',
        'your_ref'
    ];
    use HasFactory;

    public static function ExactUpdate($invoice){
      $new=  ExactSalesInvoices::firstOrCreate(
    ['invoice_id' => $invoice->InvoiceID],
    [
        'invoice_id'                => $invoice->InvoiceID,
        'amount_dc'                 => $invoice->AmountDC,
        'amount_discount'           => $invoice->AmountDiscount,
        'amount_discount_exl_vat'   => $invoice->AmountDiscountExclVat,
        'amount_fc'                 => $invoice->AmountFC,
        'amount_fc_exl_vat'         => $invoice->AmountFCExclVat,
        'created'                   => Exact::toDateTime($invoice->Created),
        'creator'                   => $invoice->Creator,
        'creator_full_name'         => $invoice->CreatorFullName,
        'currency'                  => $invoice->Currency,
        'deliver_to'                => $invoice->DeliverTo,
        'deliver_to_address'        => $invoice->DeliverToAddress,
        'deliver_to_contact_person' => $invoice->DeliverToContactPerson,
        'deliver_to_contact_person_full_name'   => $invoice->DeliverToContactPersonFullName,
        'deliver_to_name'           => $invoice->DeliverToName,
        'description'               => $invoice->Description,
        'discount'                  => $invoice->Discount,
        'discount_type'             => $invoice->DiscountType,
        'division'                  => $invoice->Division,
        'document'                  => $invoice->Document,
        'document_number'           => $invoice->DocumentNumber,
        'document_subject'          => $invoice->DocumentSubject,
        'due_date'                  => Exact::toDateTime($invoice->DueDate),
        'extra_duty_amount_fc'      => $invoice->ExtraDutyAmountFC,
        'ga_account_amount_fc'      => $invoice->GAccountAmountFC,
        'invoice_date'              => Exact::toDateTime($invoice->InvoiceDate),
        'invoice_to'                => $invoice->InvoiceTo,
        'invoice_to_contact_person' =>  $invoice->InvoiceToContactPerson,
        'invoice_to_contact_person_full_name' => $invoice->InvoiceToContactPersonFullName,
        'invoice_to_name'           => $invoice->InvoiceToName,
        'is_extra_duty'             => $invoice->IsExtraDuty,
        'journal'                   => $invoice->Journal,
        'journal_description'       => $invoice->JournalDescription,
        'modified'                  =>  isset($invoice->Modified) ? Exact::toDateTime($invoice->Modified) : '',
        'modifier'                  => $invoice->Modifier,
        'modifier_full_name'        => $invoice->ModifierFullName,
        'order_date'                => Exact::toDateTime($invoice->OrderDate),
        'ordered_by'                => $invoice->OrderedBy,
        'ordered_by_contact_person' => $invoice->OrderedByContactPerson,
        'ordered_by_contact_person_full_name' => $invoice->OrderedByContactPersonFullName,
        'ordered_by_name'           => $invoice->OrderedByName,
        'order_number'              => $invoice->OrderNumber,
        'payment_condition'         => $invoice->PaymentCondition,
        'payment_condition_description' => $invoice->PaymentConditionDescription,
        'payment_reference'         => $invoice->PaymentReference,
        'remarks'                   => $invoice->Remarks,
        'starter_sales_invoice_status' => $invoice->StarterSalesInvoiceStatus,
        'starter_sales_invoice_status_description'=> $invoice->StarterSalesInvoiceStatusDescription,
        'status'                    => $invoice->Status,
        'status_description'        => $invoice->StatusDescription,
        'type'                      => $invoice->Type,
        'type_description'          => $invoice->TypeDescription,
        'vat_amount_fc'             => $invoice->VATAmountFC,
        'vat_amount_dc'             => $invoice->VATAmountDC,
        'your_ref'                  => $invoice->YourRef,
        'warehouse'                 => $invoice->Warehouse,
        'with_holding_tax_amount_fc'=> $invoice->WithholdingTaxAmountFC,
        'with_holding_tax_base_amount'=> $invoice->WithholdingTaxBaseAmount,
        'with_holding_tax_percentage'=> $invoice->WithholdingTaxPercentage,
        'paid_status'               => 'pending'
    ]
    );

    }

}
