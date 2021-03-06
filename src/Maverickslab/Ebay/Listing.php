<?php
    /**
     * Created by PhpStorm.
     * User: Optimistic
     * Date: 19/03/2015
     * Time: 20:41
     */

    namespace Maverickslab\Ebay;


    class Listing
    {
        use InjectAPIRequester;


        /**
         * publish product to eBay
         *
         * @param     $user_token
         * @param     $listing_data
         * @param int $site_id
         *
         * @return mixed
         */
        public function publish($user_token, $listing_data, $site_id = 0, $relist = false)
        {
            $inputs = self::prepareXML($user_token, $listing_data);

            //verify listing data
            if (isset($listing_data['item_id'])) {
                if ($relist) {
                    return self::relistItem($inputs, $site_id);
                } else {
                    return self::reviseItem($inputs, $site_id);
                }
            } else {
                $verification = self::verify($inputs, $site_id, true);
            }

            if ($verification['Ack'] === "Failure")
                return $verification;

            //list item to ebay
            return self::addItem($inputs, $site_id);
        }

        //End an ebay listing

        /**
         * set up the data in a fashion that can be sent to eBay
         *
         * @param $user_token
         * @param $listing_data
         *
         * @return array
         */
        public function prepareXML($user_token, $listing_data)
        {
            $inputs['RequesterCredentials'] = [
                'eBayAuthToken' => $user_token
            ];
            $inputs['Item'] = [
                'CategoryBasedAttributesPrefill' => true,
                'CategoryMappingAllowed'         => true,
                'ConditionDescription'           => self::setDefaults($listing_data, 'condition_description'),
                'ConditionID'                    => self::setDefaults($listing_data, 'condition_id'),
                'Country'                        => self::setDefaults($listing_data, 'country'),
                'Currency'                       => self::setDefaults($listing_data, 'currency'),
                'Description'                    => self::setDefaults($listing_data, 'description'),
                'DisableBuyerRequirements'       => true,
                'DispatchTimeMax'                => self::setDefaults($listing_data, 'dispatch_max_time'),
                'IncludeRecommendations'         => true,
                'ItemID'                         => self::setDefaults($listing_data, 'item_id'),
                'ItemSpecifics'                  => [
                    'NameValueList' => self::createSpecifics(self::setDefaults($listing_data, 'item_specifics'))
                ],
                'ListingDuration'                => self::setDefaults($listing_data, 'listing_duration'),
                'ListingType'                    => self::setDefaults($listing_data, 'listing_type'),
                'Location'                       => self::setDefaults($listing_data, 'location'),
                'PaymentMethods'                 => self::setDefaults($listing_data, 'payment_methods'),
                'PayPalEmailAddress'             => self::setDefaults($listing_data, 'paypal_email_address'),
                'PictureDetails'                 => [
                    'PhotoDisplay' => 'PicturePack',
                    'PictureURL'   => self::setDefaults($listing_data, 'pictures')
                ],
                'PostalCode'                     => self::setDefaults($listing_data, 'postal_code'),
                'PrimaryCategory'                => [
                    'CategoryID' => self::setDefaults($listing_data, 'category_id')
                ],
                'ProductListingDetails'          => [
                    'BrandMPN' => [
                        'Brand' => self::setDefaults($listing_data, 'brand'),
                        'MPN'   => self::setDefaults($listing_data, 'manufacturer_part_number')
                    ],
                    'EAN'      => self::setDefaults($listing_data, 'ean'),
                    'GTIN'     => self::setDefaults($listing_data, 'gtin'),
                    'ISBN'     => self::setDefaults($listing_data, 'isbn'),
                ],
                'Quantity'                       => self::setDefaults($listing_data, 'quantity'),
                'ReturnPolicy'                   => [
                    'Description'              => self::setDefaults($listing_data['return_profile'], 'return_policy_description'),
                    'RefundOption'             => self::setDefaults($listing_data['return_profile'], 'refund_option'),
                    'ReturnsAcceptedOption'    => self::setDefaults($listing_data['return_profile'], 'returns_accepted'),
                    'ReturnsWithinOption'      => self::setDefaults($listing_data['return_profile'], 'return_within'),
                    'ShippingCostPaidByOption' => self::setDefaults($listing_data['return_profile'], 'shipping_cost_paid_by')
                ],
                'ShippingDetails'                => [
                    'CODCost'                            => self::setDefaults($listing_data['shipping_profile'], 'cost_of_delivery'),
                    'GlobalShipping'                     => self::setDefaults($listing_data['shipping_profile'], 'global_shipping'),
                    'PaymentInstructions'                => self::setDefaults($listing_data['shipping_profile'], 'payment_instructions'),
                    'SalesTax'                           => [
                        'SalesTaxPercent'       => self::setDefaults($listing_data['shipping_profile']['sales_tax'], 'sales_tax_percent'),
                        'SalesTaxState'         => self::setDefaults($listing_data['shipping_profile']['sales_tax'], 'sales_tax_state'),
                        'ShippingIncludedInTax' => self::setDefaults($listing_data['shipping_profile']['sales_tax'], 'shipping_included_in_tax'),
                    ],
                    'ShippingType'                       => self::setDefaults($listing_data['shipping_profile'], 'shipping_type'),
                    'CalculatedShippingRate'             => [
                        'OriginatingPostalCode'               => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'originating_postal_code'),
                        'ShippingIrregular'                   => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'shipping_irregular'),
                        'PackagingHandlingCost'               => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'package_handling_cost'),
                        'InternationalPackagingHandlingCosts' => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'international_package_handling_cost'),
                        'PackageDepth'                        => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'package_depth'),
                        'PackageLength'                       => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'package_length'),
                        'PackageWidth'                        => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'package_width'),
                        'ShippingPackage'                     => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'shipping_package'),
                        'WeightMajor'                         => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'weight_major'),
                        'WeightMinor'                         => self::setDefaults($listing_data['shipping_profile']['calculated_shipping_rate'], 'weight_minor')
                    ],
                    'ShippingServiceOptions'             => $this->formatShippingServiceOptions($listing_data['shipping_profile']['shipping_service_options'], "local"),
                    'InternationalShippingServiceOption' => $this->formatShippingServiceOptions($listing_data['shipping_profile']['international_shipping_service_options'], "international")
                ],
                'ShippingPackageDetails'         => [
                    'MeasurementUnit' => 'English',
                    'PackageDepth'    => self::setDefaults($listing_data['shipping_profile'], 'package_depth'),
                    'PackageLength'   => self::setDefaults($listing_data['shipping_profile'], 'package_length'),
                    'PackageWidth'    => self::setDefaults($listing_data['shipping_profile'], 'package_width'),
                    'ShippingPackage' => self::setDefaults($listing_data['shipping_profile'], 'shipping_package'),
                    'WeightMajor'     => self::setDefaults($listing_data['shipping_profile'], 'weight_major'),
                    'WeightMinor'     => self::setDefaults($listing_data['shipping_profile'], 'weight_minor'),
                ],
                'ShipToLocations'                => self::setDefaults($listing_data, 'ship_to_locations'),
                'Site'                           => self::setDefaults($listing_data, 'site'),
                'SKU'                            => self::setDefaults($listing_data, 'sku'),
                'StartPrice'                     => self::setDefaults($listing_data, 'price'),
                'Title'                          => self::setDefaults($listing_data, 'title'),
                'Variations'                     => [
                    'Variation'             => self::createVariations(self::setDefaults($listing_data, 'variations')),
                    'VariationSpecificsSet' => [
                        'NameValueList' => self::createSpecifics(self::setDefaults($listing_data, 'option_values'))
                    ]
                ]
            ];

            return self::array_walk_recursive_delete($inputs, function ($value, $key) {
                if (is_array($value)) {
                    return empty($value);
                }

                return ($value === null);
            });
        }

        /**
         * set default if an array index does not exist
         *
         * @param      $array
         * @param      $key
         * @param null $default
         *
         * @return null
         */
        private function setDefaults($array, $key, $default = [])
        {
            return isset($array[$key]) ? $array[$key] : $default;
        }

        /**
         * create product specific data
         *
         * @param $option_values
         *
         * @return array
         */
        public function createSpecifics($option_values)
        {
            $specifics = [];

            foreach ($option_values as $key => $value) {
                if (!is_null($value) && $value != "") {
                    array_push($specifics, [
                        'Name'   => $key,
                        'Value'  => $value,
                        'Source' => 'ItemSpecific'
                    ]);
                }
            }

            return $specifics;
        }

        public function formatShippingServiceOptions($shipping_service_options, $shipping_service_option_type)
        {
            $shipping_profile = array_map(function ($shipping_service_option) use ($shipping_service_option_type) {
                $data = [
                    'ShippingService'               => self::setDefaults($shipping_service_option, 'shipping_service'),
                    'ShippingServiceCost'           => self::setDefaults($shipping_service_option, 'shipping_service_cost'),
                    'ShippingServiceAdditionalCost' => self::setDefaults($shipping_service_option, 'shipping_service_additional_cost'),
                    'ShippingServicePriority'       => self::setDefaults($shipping_service_option, 'shipping_service_priority'),
                    'ShippingSurcharge'             => self::setDefaults($shipping_service_option, 'shipping_surcharge')
                ];

                if ($shipping_service_option_type === "local") {
                    $data['FreeShipping'] = self::setDefaults($shipping_service_option, 'free_shipping');
                } else {
                    $data['ShipToLocation'] = self::setDefaults($shipping_service_option, 'ship_to_locations');
                }

                return $data;

            }, $shipping_service_options);

            //\Log::info($shipping_profile);

            return $shipping_profile;
        }

        /**
         * create variation data
         *
         * @param $variations
         *
         * @return array
         */
        public function createVariations($variations)
        {
            $_variations = [];

            foreach ($variations as $variation) {
                $specifics = [];
                foreach ($variation['option_values'] as $key => $value) {
                    array_push($specifics, [
                        'Name'  => $key,
                        'Value' => $value
                    ]);
                }

                $_variations[] = [
                    'Quantity'           => $variation['quantity'],
                    'SKU'                => $variation['sku'],
                    'StartPrice'         => $variation['price'],
                    'VariationSpecifics' => [
                        'NameValueList' => $specifics
                    ]
                ];
            }

            return $_variations;
        }

        /**
         * @param array $array
         * @param callable $callback
         * @param null $userdata
         *
         * @return array
         */
        public function array_walk_recursive_delete(array &$array, callable $callback, $userdata = null)
        {
            foreach ($array as $key => &$value) {
                if (is_array($value)) {
                    $value = self::array_walk_recursive_delete($value, $callback, $userdata);
                }
                if ($callback($value, $key, $userdata)) {
                    unset($array[$key]);
                }
            }

            return $array;
        }

        /**
         * @param $inputs
         * @param int $site_id
         * @return mixed
         */
        public function relistItem($inputs, $site_id = 0)
        {
            return $this->requester->request($inputs, 'RelistItem', $site_id);
        }

        /**
         * Revise an existing eBay Listing
         *
         * @param     $user_token
         * @param     $listing_data
         * @param int $site_id
         *
         * @return mixed
         */
        public function reviseItem($inputs, $site_id = 0)
        {
            //\Log::info($inputs);
            $response = $this->requester->request($inputs, 'ReviseFixedPriceItem', $site_id);
            //\Log::info("==================");
            //\Log::info($response);

            return $response;
        }

        /**
         * Verify data been sent to eBay
         *
         * @param $user_token
         * @param $listing_data
         * @param $site_id
         *
         * @return mixed
         */
        public function verify($listing_data, $site_id, $isNew = true)
        {
            //$request_type = ($isNew) ? 'VerifyAddFixedPriceItem' : 'VerifyReviseItem';
            $request_type = 'VerifyAddFixedPriceItem';

            return $this->requester->request($listing_data, $request_type, $site_id);
        }

        /**
         * create new eBay Listing
         *
         *
         *
         * @param     $user_token
         * @param     $listing_data
         * @param int $site_id
         *
         * @return mixed
         */
        public function addItem($inputs, $site_id = 0)
        {
            return $this->requester->request($inputs, 'AddFixedPriceItem', $site_id);
        }

        /**
         * @param $user_token
         * @param $listing_data
         * @param int $site_id
         * @return mixed
         */
        public function end($user_token, $listing_data, $site_id = 0)
        {
            //TODO: format end listing request
            $inputs['RequesterCredentials'] = [
                'eBayAuthToken' => $user_token
            ];

            $inputs['ItemID'] = $listing_data['item_id'];

//          set default reason for ending listing
            $inputs['EndingReason'] = isset($listing_data['ending_reason']) ? $listing_data['ending_reason'] : "NotAvailable";

            $inputs = self::array_walk_recursive_delete($inputs, function ($value, $key) {
                if (is_array($value)) {
                    return empty($value);
                }

                return ($value === null);
            });

            return $this->requester->request($inputs, 'EndItem', $site_id);
        }

        /**
         * check if variation is enabled for a category
         *
         * @param $user_token
         * @param $category_id
         *
         * @return bool
         */
        public function variationEnabled($user_token, $category_id)
        {
            $response = (new Category($this->requester))->getFeatures($user_token, $category_id);

            if (isset($response['Category']['VariationsEnabled'])) {
                return ($response['Category']['VariationsEnabled'] == "true") ? true : false;
            }

            return false;
        }
    }