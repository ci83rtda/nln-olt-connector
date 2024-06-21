<?php

namespace App\Console\Commands;

use App\Services\Uisp\V1\UispV1Access;
use App\Services\Uisp\V2\UispApi;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $uispApi;
    public function __construct()
    {
        parent::__construct();
        $this->uispApi = new UispApi();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $this->info('Getting all the clients from UISP.');
//        $quotedServices = UispV1Access::doRequest('clients/services?statuses[]=7');
//
//        $quotedServices = json_decode($quotedServices->getBody()->getContents());
//
//        $this->info('Getting all the clients from UISP.');
        $quotedServices = UispV1Access::doRequest('clients?query=johan');

        $quotedServices = json_decode($quotedServices->getBody()->getContents());

        dd($quotedServices);
//
//
//
//        $clientSites = $this->uispApi->getSites([
//            'type' => 'client',
//            'ucrm' => true,
//            'ucrmDetails' => true,
//        ]);
//
//        $sites = [];
//        foreach ($clientSites as $clientSite) {
//
//            if ($clientSite['identification']['status'] == 'inactive') {
//                array_push($sites, $clientSite['id']);
//            }
//
//        }
//
//        $clientId = $this->choice(
//            'What is the ID of the client site?',
//            $sites,
//        );
//
//        $this->info($clientId);

//        $json = json_decode('[{"id":438,"prepaid":false,"clientId":285,"status":7,"name":"IFH15 Internet 15 Megas Hogar (Urbano)","fullAddress":"El Salto, El Salto","street1":"El Salto","street2":null,"city":"El Salto","countryId":65,"stateId":null,"zipCode":null,"note":null,"addressGpsLat":6.78359,"addressGpsLon":-75.2375795,"servicePlanId":9,"servicePlanPeriodId":49,"price":60000.0,"hasIndividualPrice":false,"totalPrice":60000.0,"currencyCode":"COP","invoiceLabel":null,"contractId":null,"contractLengthType":1,"minimumContractLengthMonths":6,"activeFrom":null,"activeTo":null,"contractEndDate":null,"discountType":0,"discountValue":null,"discountInvoiceLabel":"Descuento","discountFrom":null,"discountTo":null,"tax1Id":null,"tax2Id":null,"tax3Id":null,"invoicingStart":null,"invoicingPeriodType":1,"invoicingPeriodStartDay":1,"nextInvoicingDayAdjustment":8,"invoicingProratedSeparately":true,"invoicingSeparately":false,"sendEmailsAutomatically":null,"useCreditAutomatically":true,"servicePlanName":"IFH15 Internet 15 Megas Hogar (Urbano)","servicePlanPrice":60000.0,"servicePlanPeriod":1,"servicePlanType":"Internet","downloadSpeed":15.0,"uploadSpeed":5.0,"hasOutage":false,"unmsClientSiteStatus":null,"fccBlockId":null,"lastInvoicedDate":null,"unmsClientSiteId":"a0a72b4d-b824-4dba-9530-ca2616f87452","attributes":[],"addressData":null,"suspensionReasonId":null,"serviceChangeRequestId":null,"setupFeePrice":null,"earlyTerminationFeePrice":null,"downloadSpeedOverride":null,"uploadSpeedOverride":null,"trafficShapingOverrideEnd":null,"trafficShapingOverrideEnabled":false,"servicePlanGroupId":null,"suspensionPeriods":[],"surcharges":[]},{"id":439,"prepaid":false,"clientId":285,"status":7,"name":"CATV B\u00e1sico","fullAddress":"El Salto, El Salto","street1":"El Salto","street2":null,"city":"El Salto","countryId":65,"stateId":null,"zipCode":null,"note":null,"addressGpsLat":6.78359,"addressGpsLon":-75.2375795,"servicePlanId":24,"servicePlanPeriodId":139,"price":10000.0,"hasIndividualPrice":false,"totalPrice":10000.0,"currencyCode":"COP","invoiceLabel":null,"contractId":null,"contractLengthType":1,"minimumContractLengthMonths":null,"activeFrom":null,"activeTo":null,"contractEndDate":null,"discountType":0,"discountValue":null,"discountInvoiceLabel":"Descuento","discountFrom":null,"discountTo":null,"tax1Id":null,"tax2Id":null,"tax3Id":null,"invoicingStart":null,"invoicingPeriodType":1,"invoicingPeriodStartDay":1,"nextInvoicingDayAdjustment":8,"invoicingProratedSeparately":true,"invoicingSeparately":false,"sendEmailsAutomatically":null,"useCreditAutomatically":true,"servicePlanName":"CATV B\u00e1sico","servicePlanPrice":10000.0,"servicePlanPeriod":1,"servicePlanType":"General","downloadSpeed":null,"uploadSpeed":null,"hasOutage":null,"unmsClientSiteStatus":null,"fccBlockId":null,"lastInvoicedDate":null,"unmsClientSiteId":null,"attributes":[],"addressData":null,"suspensionReasonId":null,"serviceChangeRequestId":null,"setupFeePrice":null,"earlyTerminationFeePrice":null,"downloadSpeedOverride":null,"uploadSpeedOverride":null,"trafficShapingOverrideEnd":null,"trafficShapingOverrideEnabled":false,"servicePlanGroupId":null,"suspensionPeriods":[],"surcharges":[]}]');
//
//        $collect = collect($json)->where('servicePlanId',24)->first();
//
//        dd($collect);

    }
}
