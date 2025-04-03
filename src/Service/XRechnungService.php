<?php

declare(strict_types=1);

/*
 * This file is part of the guesthouse administration package.
 *
 * (c) Alexander Elchlepp <info@fewohbee.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Enum\PaymentMeansCode;
use App\Entity\Invoice;
use App\Entity\InvoiceSettingsData;
use horstoeko\zugferd\codelists\ZugferdCurrencyCodes;
use horstoeko\zugferd\codelists\ZugferdElectronicAddressScheme;
use horstoeko\zugferd\codelists\ZugferdInvoiceType;
use horstoeko\zugferd\codelists\ZugferdUnitCodes;
use horstoeko\zugferd\codelists\ZugferdVatCategoryCodes;
use horstoeko\zugferd\codelists\ZugferdVatTypeCodes;
use horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Enum\InvoiceStatus;
use InvalidArgumentException;

class XRechnungService
{
    public function __construct(private InvoiceService $is, private TranslatorInterface $translator) {}

    public function createInvoice(Invoice $invoice, InvoiceSettingsData $settings): string
    {
        if (empty($invoice->getCountry())) {
            throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.buyerCountry'));
        }

        if(!($invoice->getPaymentMeans() instanceof PaymentMeansCode)) {
            throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.paymentmeans'));
        }

        $documentBuilder = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_XRECHNUNG_3);

        // General invoice Information

        $documentBuilder->setDocumentInformation(
            $invoice->getNumber(),                                     // Invoice number (BT-1)
            ZugferdInvoiceType::INVOICE,                        // Type "Invoice" (BT-3)
            $invoice->getDate(),      // Invoice date (BT-2)
            ZugferdCurrencyCodes::EURO,                          // Invoice currency is EUR (Euro) (BT-5)
            $this->translator->trans('invoice.number.short').'-'.$invoice->getNumber(),                   // A document title
        );

        // seller information
        $documentBuilder->setDocumentSeller($settings->getCompanyName()); // company name of the hotel
        $documentBuilder->addDocumentSellerTaxNumber($settings->getTaxNumber());  // Tax number
        $documentBuilder->addDocumentSellerVATRegistrationNumber($settings->getVatID()); // Umsatzsteuer-Identifikationsnummer / VAT ID, not always available
        $documentBuilder->setDocumentSellerAddress($settings->getCompanyAddress(), '', '', $settings->getCompanyPostCode(), $settings->getCompanyCity(), $settings->getCompanyCountry());
        $documentBuilder->setDocumentSellerContact($settings->getContactName(), $settings->getContactDepartment(), $settings->getContactPhone(), null, $settings->getContactMail());
        $documentBuilder->setDocumentSellerCommunication(ZugferdElectronicAddressScheme::UNECE3155_EM, $settings->getCompanyInvoiceMail());

        // customer information
        $customerName = $invoice->getSalutation() . ' ' . $invoice->getFirstname() . ' ' . $invoice->getLastname();
        $documentBuilder->setDocumentBuyer((!empty($invoice->getCompany()) ? $invoice->getCompany() : $customerName));
        $documentBuilder->setDocumentBuyerAddress($invoice->getAddress(), '', '', $invoice->getZip(), $invoice->getCity(), $invoice->getCountry());
        $documentBuilder->setDocumentBuyerContact($customerName, null, $invoice->getPhone(), null, $invoice->getEmail());
        $documentBuilder->setDocumentBuyerCommunication(ZugferdElectronicAddressScheme::UNECE3155_EM, $invoice->getEmail());

        $mandateReference = null;
        if($invoice->getPaymentMeans() === PaymentMeansCode::CASH) {
            $documentBuilder->addDocumentPaymentMean(ZugferdPaymentMeans::UNTDID_4461_10);
        }
        // CREDIT TRANSFER (BG-17) must be supplied with IBAN (mandatory) and BIC (optional)
        if($invoice->getPaymentMeans() === PaymentMeansCode::SEPA_CREDIT_TRANSFER) {
            if(empty($settings->getAccountIBAN())) {
                throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.IBAN'));
            }
            $documentBuilder->addDocumentPaymentMeanToCreditTransfer($settings->getAccountIBAN(), $settings->getAccountName(), null, $settings->getAccountBIC()); // Payment information
        }
        // CREDIT TRANSFER (BG-17) must be supplied with IBAN (mandatory) and BIC (mandatory)
        if($invoice->getPaymentMeans() === PaymentMeansCode::CREDIT_TRANSFER) {
            if(empty($settings->getAccountIBAN()) || empty($settings->getAccountBIC())) {
                throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.IBAN_BIC'));
            }
            $documentBuilder->addDocumentPaymentMeanToCreditTransferNonSepa($settings->getAccountIBAN(), $settings->getAccountName(), null, $settings->getAccountBIC()); // Payment information
        }
        // CARD INFORMATION (BG-18) must be supplied with card number (mandatory) and card holder (optional)
        if($invoice->getPaymentMeans() === PaymentMeansCode::CARD_PAYMENT) {
            if(empty($invoice->getCardNumber())) {
                throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.cardNumber'));
            }
            $documentBuilder->addDocumentPaymentMeanToPaymentCard('', $invoice->getCardNumberShort(), $invoice->getCardHolder());
        }

        // DIRECT DEBIT (BG-19) must be supplied with buyer IBAN (mandatory) and creditor identifier (mandatory)
        if($invoice->getPaymentMeans() === PaymentMeansCode::SEPA_DIRECT_DEBIT) {
            if(empty($invoice->getCustomerIBAN()) || empty($invoice->getMandateReference())) {
                throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.IBANBuyer'));
            }
            if(empty($settings->getCreditorReference())) {
                throw new InvalidArgumentException($this->translator->trans('invoice.xrechnung.mandatory.creditorReference'));
            }
            $documentBuilder->addDocumentPaymentMeanToDirectDebit($invoice->getCustomerIBAN(), $settings->getCreditorReference());
            $mandateReference = $invoice->getMandateReference();
        }


        // payment terms and due date
        $dueDate = (!is_null($settings->getPaymentDueDays()) ? $invoice->getDate()->modify('+' . $settings->getPaymentDueDays() . ' days') : null);
        $documentBuilder->addDocumentPaymentTerm($settings->getPaymentTerms(), $dueDate, $mandateReference); // Payment term

        $documentBuilder->setDocumentBuyerReference($invoice->getBuyerReference() !== null ? $invoice->getBuyerReference() : 'not used'); // Leitweg-ID, required for B2G communication. Here we set something because its not relevant for B2B or B2C


        // invoice positions
        $pos = 1;
        $netSums = [];
        /* @var $apartmentPosition \App\Entity\InvoiceAppartment */
        foreach($invoice->getAppartments() as $apartmentPosition) {
            $sum = round($apartmentPosition->getNetPrice() * $apartmentPosition->getAmount(), 2);
            $documentBuilder->addNewPosition((string)$pos);
            $documentBuilder->setDocumentPositionProductDetails($apartmentPosition->getDescription(), $apartmentPosition->getStartDate()->format('d.m.Y') .' - ' . $apartmentPosition->getEndDate()->format('d.m.Y'));
            $documentBuilder->setDocumentPositionNetPrice($apartmentPosition->getNetPrice());
            $documentBuilder->setDocumentPositionQuantity($apartmentPosition->getAmount(), ZugferdUnitCodes::REC20_PIECE);
            $documentBuilder->addDocumentPositionTax(ZugferdVatCategoryCodes::STAN_RATE, ZugferdVatTypeCodes::VALUE_ADDED_TAX, $apartmentPosition->getVat());
            $documentBuilder->setDocumentPositionLineSummation($sum);
            $netSums[$apartmentPosition->getVat()] = ($netSums[$apartmentPosition->getVat()] ?? 0) + $sum;
            $pos++;
        }
        
        // invoice misc positions
        /* @var $miscPosition \App\Entity\InvoicePosition */
        foreach($invoice->getPositions() as $miscPosition) {
            $sum = round($miscPosition->getNetPrice() * $miscPosition->getAmount(), 2);
            $documentBuilder->addNewPosition((string)$pos);
            $documentBuilder->setDocumentPositionProductDetails($miscPosition->getDescription(), '');
            $documentBuilder->setDocumentPositionNetPrice($miscPosition->getNetPrice());
            $documentBuilder->setDocumentPositionQuantity($miscPosition->getAmount(), ZugferdUnitCodes::REC20_PIECE);
            $documentBuilder->addDocumentPositionTax(ZugferdVatCategoryCodes::STAN_RATE, ZugferdVatTypeCodes::VALUE_ADDED_TAX, $miscPosition->getVat());
            $documentBuilder->setDocumentPositionLineSummation($sum);
            $netSums[$miscPosition->getVat()] = ($netSums[$miscPosition->getVat()] ?? 0) + $sum;
            $pos++;
        }
        
        $vatSum = 0;
        $netSum = 0;
        foreach($netSums as $vat=>$sum) {
            $documentBuilder->addDocumentTax(ZugferdVatCategoryCodes::STAN_RATE, ZugferdVatTypeCodes::VALUE_ADDED_TAX, $sum, $sum*($vat/100), $vat);
            $vatSum += round($sum*($vat/100), 2);
            $netSum += $sum;
        }

        $prepaidAmount = null;
        $duePayableAmount = $netSum + $vatSum;
        if($invoice->getStatus() === InvoiceStatus::{'PAID'}->value) {
            $prepaidAmount = $netSum + $vatSum;
            $duePayableAmount = 0.0;
        } // todo collect amount if status is prepaid
            
        $documentBuilder->setDocumentSummation($netSum + $vatSum, $duePayableAmount, $netSum, 0.0, 0.0, $netSum, $vatSum, null, $prepaidAmount);

        return $documentBuilder->getContent();
    }
}
