<?php
    /**
     * Bangla QR gateway
     *
     * The customer scans the merchant's Bangla QR with ANY bank / mobile-banking app and pays.
     * The payment is verified automatically from the SMS that the PipraPay Companion app forwards,
     * so the admin must choose which provider's SMS the QR money arrives from ("source" setting).
     */
    class BanglaQrGateway
    {
        /* provider|type pairs that PipraPay can read from SMS (see MFSMessageVerified) */
        private function sources()
        {
            return [
                'tallypay|Merchant' => 'TallyPay - Bangla QR (Super QR)',
                'bkash|Merchant'   => 'bKash - Merchant (QR linked to a bKash merchant wallet)',
                'bkash|Personal'   => 'bKash - Personal',
                'nagad|Personal'   => 'Nagad - Personal',
                'rocket|Personal'  => 'Rocket - Personal',
                'upay|Personal'    => 'Upay - Personal',
                'tap|Personal'     => 'Tap - Personal',
                'cellfin|Personal' => 'Cellfin - Personal',
                'okwallet|Personal'=> 'OK Wallet - Personal',
                'mcash|Personal'   => 'mCash - Personal',
                'pathaopay|Personal' => 'Pathao Pay - Personal',
            ];
        }

        public function info()
        {
            return [
                'title'        => 'Bangla QR',
                'logo'         => 'assets/logo.jpg',
                'currency'     => 'BDT',
                'tab'          => 'mfs',

                'gateway_type' => 'automation',
                'sender_key'   => 'tallypay',
                'sender_type'  => 'Merchant',

                // used by the checkout page
                'display_name' => 'Bangla QR',
                'any_app'      => true,
                'direct_qr'    => true,     // QR-first page: no payer number step
                'allow_slip'   => true,     // customer may upload a payment screenshot (admin reviews it)
            ];
        }

        /* Called by PipraPay with the saved gateway settings: picks the SMS source chosen by the admin */
        public function resolve($info, $options = [])
        {
            $src = (string)($options['source'] ?? '');
            $all = $this->sources();

            if ($src !== '' && isset($all[$src])) {
                list($key, $type) = explode('|', $src, 2);
                $info['sender_key']  = $key;
                $info['sender_type'] = $type;
            }

            return $info;
        }

        public function color()
        {
            return [
                'primary_color'  => '#006A4E',
                'text_color'     => '#FFFFFF',
                'btn_color'      => '#006A4E',
                'btn_text_color' => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'qr_payload',
                    'label' => 'Bangla QR text (starts with 000201...) - makes a QR with the amount locked',
                    'type'  => 'text',
                ],
                [
                    'name'  => 'qr_code',
                    'label' => 'Bangla QR Image (used only if the QR text above is empty)',
                    'type'  => 'image',
                ],
                [
                    'name'     => 'source',
                    'label'    => 'Payment SMS source (where the QR money arrives)',
                    'type'     => 'select',
                    'options'  => $this->sources(),
                    'value'    => 'tallypay|Merchant',
                    'required' => true,
                    'multiple' => false,
                ],
            ];
        }

        public function supported_languages()
        {
            return [
                'en' => 'English',
                'bn' => 'বাংলা',
            ];
        }

        public function lang_text()
        {
            return [
                '1' => [
                    'en' => 'Open any bank or mobile banking app that supports Bangla QR.',
                    'bn' => 'Bangla QR সাপোর্ট করে এমন যেকোনো ব্যাংক বা মোবাইল ব্যাংকিং অ্যাপ খুলুন।',
                ],
                '2' => [
                    'en' => 'Scan the QR code shown on this page.',
                    'bn' => 'এই পেজে দেখানো QR কোডটি স্ক্যান করুন।',
                ],
                '3' => [
                    'en' => 'Pay exactly {amount} {currency}.',
                    'bn' => 'ঠিক {amount} {currency} পেমেন্ট করুন।',
                ],
            ];
        }

        public function instructions($data)
        {
            return [
                [
                    'icon' => '',
                    'text' => '1',
                    'copy' => false,
                ],
                [
                    'icon' => '',
                    'text' => '2',
                    'copy' => false,
                    'action' => [
                        'type'  => 'image',
                        'label' => 'QR',
                        'value' => $data['options']['qr_code'] ?? '',
                    ],
                ],
                [
                    'icon' => '',
                    'text' => '3',
                    'copy' => true,
                    'value' => $data['transaction']['local_net_amount'],
                    'vars' => [
                        '{amount}'   => $data['transaction']['local_net_amount'],
                        '{currency}' => $data['transaction']['local_currency'],
                    ],
                ],
            ];
        }
    }
