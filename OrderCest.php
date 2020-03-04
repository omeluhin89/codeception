<?php

use \PHPUnit\Framework\Assert;

class OrderCest
{
    /*Несколько функций которые я использую в тесте*/
    /**
     * Метод удаляет цифру "0" в датах типа "04 февраля" и сравнивает дату выгрузок на страницах оформления заказа и спасибо за заказ
     *
     * @param $I
     */
    public static function assertDateShipment($I) {
        $dateFirstShipment = $I->grabTextFrom(OrderForm::$valuesOrderOnThanksPage['Доставка 1-й посылки']);
        $dateSecondShipment = $I->grabTextFrom(OrderForm::$valuesOrderOnThanksPage['Доставка 2-й посылки']);
        $dateThirdShipment = $I->grabTextFrom(OrderForm::$valuesOrderOnThanksPage['Доставка 3-й посылки']);
        if ($dateFirstShipment[0] === '0') {
            $dateFirstShipment = substr($dateFirstShipment, 1);
        }
        if ($dateSecondShipment[0] === '0'){
            $dateSecondShipment = substr($dateSecondShipment, 1);
        }
        if ($dateThirdShipment[0] === '0'){
            $dateThirdShipment = substr($dateThirdShipment, 1);
        }
        $I->assertSame(OrderForm::$dateShipment['Посылка 1'] . ' 2020', $dateFirstShipment);
        $I->assertSame(OrderForm::$dateShipment['Посылка 2'] . ' 2020', $dateSecondShipment);
        $I->assertSame(OrderForm::$dateShipment['Посылка 3'] . ' 2020', $dateThirdShipment);
    }

    public static function changeCityThroughApi($settlement_id, $I)
    {
        if ($I instanceof AcceptanceTester or $I instanceof Acceptance_mobileTester)
        {
            switch ($settlement_id){
                case 'Москва':
                    $settlement_id = 1686293227;
                    break;
                case 'Екатеринбург':
                    $settlement_id = 27503892;
                    break;
                case 'Сима-ленд':
                    $settlement_id = 3204066170;
                    break;
                case 'Магадан':
                    $settlement_id = 191709960;
                    break;
                case 'Казань':
                    $settlement_id = 27504067;
                    break;
                case 'Путино':
                    $settlement_id = 1063938202;
                    break;
                case 'Тюмень':
                    $settlement_id = 27505666;
                    break;
                case 'Санкт-Петербург':
                    $settlement_id = 27490597;
                    break;
                case 'Уфа':
                    $settlement_id = 27504327;
                    break;
                case 'Воронеж':
                    $settlement_id = 27505044;
                    break;
                case 'Выра':
                    $settlement_id = 270601154;
                    break;
            }
            if ($I instanceof AcceptanceTester) {
                $js = '$.ajax({
                        type: "POST",
                        url: "https://testben.sima-land.ru/api/v3/settlement-form/",
                        data: {settlement_id: ' . $settlement_id . ' }
                        })';
            } elseif ($I instanceof Acceptance_mobileTester) {
                $js = '$.ajax({
                        type: "POST",
                        url: "https://m.testben.sima-land.ru/api/v3/settlement-form/",
                        data: {settlement_id: ' . $settlement_id . ' }
                        })';
            }
            $I->executeJS($js);
        }
    }


    /**
     * Метод нахождения даты каждой отгрузки
     *
     * @param $I
     * @throws Exception
     */
    public static function shipmentDate($I)
    {
        if ($I instanceof AcceptanceTester) {
            for ($i = 1; $i <= 3; $i++) {
                if ($I->seeText('Посылка 1', '.shipment-wrapper:nth-child(' . $i . ') .parcel-title')) {
                    $firstShipmentDate = $I->grabTextFrom('.shipment-wrapper:nth-child(' . $i . ') .selected-date');
                    preg_match('/– (\d+ [\W]+)/', $firstShipmentDate, $result);
                    OrderForm::$dateShipment['Посылка 1'] = $result[1];
                } elseif ($I->seeText('Посылка 2', '.shipment-wrapper:nth-child(' . $i . ') .parcel-title')) {
                    $secondShipmentDate = $I->grabTextFrom('.shipment-wrapper:nth-child(' . $i . ') .selected-date');
                    preg_match('/– (\d+ [\W]+)/', $secondShipmentDate, $result);
                    OrderForm::$dateShipment['Посылка 2'] = $result[1];
                } elseif ($I->seeText('Посылка 3', '.shipment-wrapper:nth-child(' . $i . ') .parcel-title')) {
                    $thirdShipmentDate = $I->grabTextFrom('.shipment-wrapper:nth-child(' . $i . ') .selected-date');
                    preg_match('/– (\d+ [\W]+)/', $thirdShipmentDate, $result);
                    OrderForm::$dateShipment['Посылка 3'] = $result[1];
                }
            }
        }
    }

    /* Тест*/
    /**
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function checkOrderWithThreeShipmentsDeliveryAddress(AcceptanceTester $I)
    {
        $I->amOnSegmentPage(AuthPage::$acceptanceRoute);
        $I->wantTo('проверка заказа с тремя отгрузками с адресной доставкой');
        $I->hideWidgets();
        $page = new AuthPage($I);
        $page->selectLoginForm();
        $page->login(AuthPage::$testUser);
        $page->checkUserAuthStatus('auth', $I);
        $I->amOnSegmentPage(CartPage::$acceptanceRoute);
        CartPage::apiDelItemsFromCart($I);
        self::changeCityThroughApi('Москва', $I);
        CartPage::addApiItemToCart($I,3838757, 4, self::ITEM_SID_MOSCOW_STOCK, 1, self::ITEM_SID_PARTNER, 2);
        $I->setCookie(OrderPage::cookieDelivery, 'prod');
        $I->amOnSegmentPage(CartPage::$acceptanceRoute);
        $I->waitPageLoad();
        $I->waitForElementNotVisible(CartPage::$preLoaderNewSelectorInCart);
        $I->waitForElementNotVisible(CartPage::$preloaderTotalSumBlockSelector);
        $I->waitForElementVisible(CartPage::$nameOfTheFirstProductInCartSelector);
        $I->waitForElementVisible(CartPage::$checkoutButtonSelector);
        $I->click(CartPage::$checkoutButtonSelector);
        if ($I->waitPageElement(CartPage::$popupReorderingSelector)) {
            $I->waitForElement(CartPage::$popupCheckoutButtonSelector);
            CartPage::scrollToCheckout($I);
            $I->waitForElementVisible(CartPage::$popupCheckoutButtonSelector);
            $I->click(CartPage::$popupCheckoutButtonSelector);
        }
        $I->waitPageLoad();
        $I->waitForElementNotVisible(CatalogPage::$preLoaderNewSelector);
        $I->waitForElementNotVisible(CatalogPage::$preLoaderThanksOrderSelector);
        OrderPage::makeOrder(OrderPage::$userAddressDeliveryInMoscow, $I);
        OrderPage::checkTotalSum($I);
        $I->waitForElement(OrderPage::$activeCheckboxThreeShipmentsSelector);
        OrderForm::findDeliveryThirdShipmentSelector($I);
        $I->click(OrderPage::$activeCheckboxThreeShipmentsSelector);
        $shipments = $I->grabMultiple(OrderPage::$shipmentBlockSelector);
        $shipments_count = count($shipments);
        $I->assertEquals(1, $shipments_count);
        $shipmentCostInShipmentBlock = $I->grabTextFrom('.shipment-wrapper:nth-child(1) .b');
        $shipmentsCostInTotalSumBlock = $I->grabTextFrom(OrderForm::$valueDeliveryThirdShipmentBlock);
        $I->assertSame($shipmentCostInShipmentBlock, $shipmentsCostInTotalSumBlock);
        $I->click(OrderPage::$disableCheckboxThreeShipmentsSelector);
        $totalSum1 = OrderPage::getPriceWithCents(OrderPage::$totalSumMakeOrderSelector, $I);
        echo "TotalSumOrder: $totalSum1\n";
        $shipments = $I->grabMultiple(OrderPage::$shipmentBlockSelector);
        $shipments_count = count($shipments);
        $I->assertEquals(3, $shipments_count);
        self::shipmentDate($I);
        OrderPage::sendOrder($I);
        OrderPage::checkOrderStatus($I);
        OrderForm::findFieldDataOrderSelector($I);
        self::assertDateShipment($I);
        OrderPage::$totalCostOnThanksPage = OrderPage::getPriceWithCents(OrderPage::$totalCostSumOrderDoneSelector, $I);
        echo "TotalSumThanksPage: " . OrderPage::$totalCostOnThanksPage . "\n";
        if (OrderPage::$totalCostOnThanksPage != $totalSum1) {
            assert::fail("Итоговая сумма рассчитывается неверно. Итоговая сумма на странице оформления $totalSum1, а на странице спасибо за заказ ". OrderPage::$totalCostOnThanksPage ."\n");
        }
        if (!$I->seeText('Доставка службой Сима‑ленд до двери', OrderForm::$valuesOrderOnThanksPage['Получение'])) {
            assert::fail("На странице 'Спасибо за заказ' неверное наименование доставки\n");
        };
        OrderPage::checkThreeShipmentsInMyOrderPage($I);
    }
}

