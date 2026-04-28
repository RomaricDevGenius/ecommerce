<?php

use Carbon\Carbon;
use App\Models\PreorderProductReview;
use App\Models\Tax;
use App\Models\Cart;
use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\Addon;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Seller;
use App\Models\Upload;
use App\Models\Wallet;
use App\Models\Carrier;
use App\Models\Country;
use App\Models\Product;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Wishlist;
use App\Models\Attribute;
use App\Models\ClubPoint;
use App\Models\FlashDeal;
use App\Models\CouponUsage;
use App\Models\DeliveryBoy;
use App\Models\OrderDetail;
use App\Models\PickupPoint;
use App\Models\Translation;
use App\Models\BlogCategory;
use App\Models\Conversation;
use App\Models\FollowSeller;
use App\Models\ProductStock;
use App\Models\CombinedOrder;
use App\Models\SellerPackage;
use App\Models\AffiliateConfig;
use App\Models\AffiliateOption;
use App\Models\BusinessSetting;
use App\Models\CustomerPackage;
use App\Models\CustomerProduct;
use App\Utility\SendSMSUtility;;
use App\Models\AuctionProductBid;
use App\Models\ManualPaymentMethod;
use App\Models\SellerPackagePayment;
use App\Utility\NotificationUtility;
use App\Http\Resources\V2\CarrierCollection;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\ClubPointController;
use App\Http\Controllers\CommissionController;
use AizPackages\ColorCodeConverter\Services\ColorCodeConverter;
use App\Models\Address;
use App\Models\AppTranslation;
use App\Models\Area;
use App\Models\CustomerPackagePayment;
use App\Models\CustomLabel;
use App\Models\CustomSaleAlert;
use App\Models\ElementStyle;
use App\Models\ElementType;
use App\Models\EmailTemplate;
use App\Models\FlashDealProduct;
use App\Models\LastViewedProduct;
use App\Models\PaymentMethod;
use App\Models\UserCoupon;
use App\Models\NotificationType;
use App\Models\PreorderConversationMessage;
use App\Models\PreorderConversationThread;
use App\Models\PreorderProduct;
use App\Utility\EmailUtility;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;

//sensSMS function for OTP
if (!function_exists('sendSMS')) {
    function sendSMS($to, $from, $text, $template_id)
    {
        return SendSMSUtility::sendSMS($to, $from, $text, $template_id);
    }
}

//highlights the selected navigation on admin panel
if (!function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route && (url()->current() != url('/admin/website/custom-pages/edit/home'))) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('areActiveRoutesHome')) {
    function areActiveRoutesHome(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE");
    }
}

/**
 * Save JSON File
 * @return Response
 */
if (!function_exists('convert_to_usd')) {
    function convert_to_usd($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'USD')->first()->exchange_rate;
    }
}

if (!function_exists('convert_to_kes')) {
    function convert_to_kes($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'KES')->first()->exchange_rate;
    }
}

// get all active countries
if (!function_exists('get_active_countries')) {
    function get_active_countries()
    {
        $country_query = Country::query();
        return $country_query->isEnabled()->get();
    }
}

//filter products based on vendor activation system
if (!function_exists('filter_products')) {
    function filter_products($products)
    {

        $products = $products->isApprovedPublished()->where('auction_product', 0);

        if (!addon_is_activated('wholesale')) {
            $products = $products->where('wholesale_product', 0);
        }
        $verified_sellers = verified_sellers_id();
        if (get_setting('vendor_system_activation') == 1) {
            return $products->where(function ($p) use ($verified_sellers) {
                $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers) {
                    $q->whereIn('user_id', $verified_sellers);
                });
            });
        } else {
            return $products->where('added_by', 'admin');
        }
    }
}

//cache products based on category
if (!function_exists('get_cached_products')) {
    function get_cached_products($category_id = null)
    {
        return Cache::remember('products-category-' . $category_id, 86400, function () use ($category_id) {
            return filter_products(Product::where('category_id', $category_id))->latest()->take(5)->get();
        });
    }
}

if (!function_exists('verified_sellers_id')) {
    function verified_sellers_id()
    {
        return Cache::rememberForever('verified_sellers_id', function () {
            return Shop::where('verification_status', 1)->pluck('user_id')->toArray();
        });
    }
}

// if (!function_exists('unbanned_sellers_id')) {
//     function unbanned_sellers_id()
//     {
//         return Cache::rememberForever('unbanned_sellers_id', function () {
//             return App\Models\User::where('user_type', 'seller')->where('banned', 0)->pluck('id')->toArray();
//         });
//     }
// }

if (!function_exists('get_system_default_currency')) {
    function get_system_default_currency()
    {
        return Cache::remember('system_default_currency', 86400, function () {
            return Currency::findOrFail(get_setting('system_default_currency'));
        });
    }
}

//converts currency to home default currency
if (!function_exists('convert_price')) {
    function convert_price($price)
    {
        if (Session::has('currency_code') && (Session::get('currency_code') != get_system_default_currency()->code)) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(Session::get('currency_exchange_rate'));
        }

        if (
            request()->header('Currency-Code') &&
            request()->header('Currency-Code') != get_system_default_currency()->code
        ) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(request()->header('Currency-Exchange-Rate'));
        }
        return $price;
    }
}

//gets currency symbol
if (!function_exists('currency_symbol')) {
    function currency_symbol()
    {
        if (Session::has('currency_symbol')) {
            return Session::get('currency_symbol');
        }
        if (request()->header('Currency-Code')) {
            return request()->header('Currency-Code');
        }
        return get_system_default_currency()->symbol;
    }
}

//formats currency
if (!function_exists('format_price')) {
    function format_price($price, $isMinimize = false)
    {
        // Format: 4.500.000 FCFA (points pour milliers, 0 décimales, symbole à la fin avec espace)
        // Utiliser number_format avec point pour milliers, puis supprimer les décimales
        $fomated_price = number_format($price, 0, ',', '.');
        // S'assurer qu'il n'y a pas de décimales (déjà fait avec 0 décimales)

        // Minimize the price
        if ($isMinimize) {
            $temp = number_format($price / 1000000000, 0, ",", "");

            if ($temp >= 1) {
                $fomated_price = $temp . "B";
            } else {
                $temp = number_format($price / 1000000, 0, ",", "");
                if ($temp >= 1) {
                    $fomated_price = $temp . "M";
                }
            }
        }

        // Format: [Montant] [Symbole] -> "4.500.000 FCFA"
        $symbol = currency_symbol();
        // Vérification robuste : null, chaîne vide, ou false -> utiliser 'FCFA' par défaut
        if ($symbol === null || $symbol === '' || $symbol === false || trim($symbol) === '') {
            $symbol = 'FCFA';
        }
        // S'assurer que le symbole est une chaîne valide
        $symbol = trim((string)$symbol);
        if (empty($symbol)) {
            $symbol = 'FCFA';
        }
        return $fomated_price . ' ' . $symbol;
    }
}

//formats price to home default price with convertion
if (!function_exists('single_price')) {
    function single_price($price)
    {
        return format_price(convert_price($price));
    }
}

if (!function_exists('discount_in_percentage')) {
    function discount_in_percentage($product)
    {
        $base = home_base_price($product, false);
        $reduced = home_discounted_base_price($product, false);
        $discount = $base - $reduced;
        $dp = ($discount * 100) / ($base > 0 ? $base : 1);
        return round($dp);
    }
}

//Shows Price on page based on carts
if (!function_exists('cart_product_price')) {
    function cart_product_price($cart_product, $product, $formatted = true, $tax = true)
    {
        if ($product->auction_product == 0) {
            $str = '';
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $price = 0;
            $product_stock = $product->stocks->where('variant', $str)->first();
            if ($product_stock) {
                $price = $product_stock->price;
            }

            if ($product->wholesale_product) {
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $cart_product['quantity'])->where('max_qty', '>=', $cart_product['quantity'])->first();
                if ($wholesalePrice) {
                    $price = $wholesalePrice->price;
                }
            }

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $price -= ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $price -= $product->discount;
                }
            }
        } else {
            $price = $product->bids->max('amount');
        }

        //calculation of taxes
        if ($tax) {
            $taxAmount = 0;
            foreach ($product->taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $taxAmount += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $taxAmount += $product_tax->tax;
                }
            }
            $price += $taxAmount;
        }
        if ($formatted) {
            return format_price(convert_price($price));
        } else {
            return $price;
        }
    }
}

if (!function_exists('cart_product_tax')) {
    function cart_product_tax($cart_product, $product, $formatted = true)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        //calculation of taxes
        $tax = 0;
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        if ($formatted) {
            return format_price(convert_price($tax));
        } else {
            return $tax;
        }
    }
}

if (!function_exists('cart_product_gst')) {
    function cart_product_gst($cart_product, $product, $formatted = true)
    {
        if (!addon_is_activated('gst_system')) {
            return 0;
        }
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        // $product_stock = $product->stocks->where('variant', $str)->first();
        // $price = $product_stock->price;

        $price = 0;
        $product_stock = $product->stocks->where('variant', $str)->first();
        if ($product_stock) {
            $price = $product_stock->price * $cart_product['quantity'];
        }

        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $cart_product['quantity'])->where('max_qty', '>=', $cart_product['quantity'])->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price * $cart_product['quantity'];
            }
        }
        if ($product->auction_product) {
            $price= $cart_product['price'] * $cart_product['quantity'];
        }

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }
        // Subtract coupon discount
        $price-=$cart_product['discount'];
        //Subtract shipping_cost
        $price+=$cart_product['shipping_cost'];



        //calculation of gst
        $gst = 0;
        $gst += ($price * $product->gst_rate) / 100;

        if ($formatted) {
            return format_price(convert_price($gst));
        } else {
            return $gst;
        }
    }
}

if (!function_exists('cart_product_discount')) {
    function cart_product_discount($cart_product, $product, $formatted = false)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;

        //discount calculation
        $discount_applicable = false;
        $discount = 0;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $discount = ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $discount = $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// all discount
if (!function_exists('carts_product_discount')) {
    function carts_product_discount($cart_products, $formatted = false)
    {
        $discount = 0;
        foreach ($cart_products as $key => $cart_product) {
            $str = '';
            $product = \App\Models\Product::find($cart_product['product_id']);
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $product_stock = $product->stocks->where('variant', $str)->first();
            $price = $product_stock->price;

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $discount += ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $discount += $product->discount;
                }
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// carts coupon discount
if (!function_exists('carts_coupon_discount')) {
    function carts_coupon_discount($code, $formatted = false)
    {
        $coupon = Coupon::where('code', $code)->first();
        $coupon_discount = 0;
        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);
                    $carts = Cart::where('user_id', Auth::user()->id)
                        ->where('owner_id', $coupon->user_id)
                        ->get();
                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($coupon_discount > 0) {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => $coupon_discount / count($carts),
                        ]
                    );
            } else {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => 0,
                            'coupon_code' => null,
                        ]
                    );
            }
        }
        if ($formatted) {
            return format_price(convert_price($coupon_discount));
        } else {
            return $coupon_discount;
        }
    }
}


//Shows Price on page based on low to high
if (!function_exists('home_price')) {
    function home_price($product, $formatted = true)
    {
        $lowest_price = $product->unit_price;
        $highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }
        
        if(addon_is_activated('gst_system')){
            $lowest_price += ($lowest_price * $product->gst_rate) / 100;
            $highest_price += ($highest_price * $product->gst_rate) / 100;
        }
        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Shows Bad Results in Seller Hompapage Retruns
if (!function_exists('seller_homepage_urls')) {
    function seller_homepage_urls($slug)
    {
        if ($slug == "bad" && env('DEMO_MODE') != 'On') {
            return false;
        }
        return true;
    }
}

//Shows Price on page based on low to high with discount
if (!function_exists('home_discounted_price')) {
    function home_discounted_price($product, $formatted = true)
    {
        $lowest_price = $product->unit_price;
        $highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $lowest_price -= ($lowest_price * $product->discount) / 100;
                $highest_price -= ($highest_price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $lowest_price -= $product->discount;
                $highest_price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }
        if(addon_is_activated('gst_system')){
            $lowest_price += ($lowest_price * $product->gst_rate) / 100;
            $highest_price += ($highest_price * $product->gst_rate) / 100;
        }

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Generates Fromatted DateTime
if (!function_exists('TimeDateFormatter')) {
    function TimeDateFormatter()
    {
        date_default_timezone_set('UTC');
        $timestamp = time();
        return pow(substr($timestamp, -10, 9),2);
    }
}

//Shows Base Price
if (!function_exists('home_base_price_by_stock_id')) {
    function home_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $price = $product_stock->price;
        $tax = 0;

        foreach ($product_stock->product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;
        return format_price(convert_price($price));
    }
}


if (!function_exists('home_base_price')) {
    function home_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;
        if (addon_is_activated('gst_system')){
        $price += ($price * $product->gst_rate) / 100;
        }
        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}

//Shows Base Price with discount
if (!function_exists('home_discounted_base_price_by_stock_id')) {
    function home_discounted_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $product = $product_stock->product;
        $price = $product_stock->price;
        $tax = 0;

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;

        return format_price(convert_price($price));
    }
}


//Shows Base Price with discount
if (!function_exists('home_discounted_base_price')) {
    function home_discounted_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }
        if(addon_is_activated('gst_system')){
            $price += ($price * $product->gst_rate) / 100;
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;


        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}

if (!function_exists('renderStarRating')) {
    function renderStarRating($rating, $maxRating = 5)
    {
        $fullStar = "<i class = 'las la-star active mr-1'></i>";
        $halfStar = "<i class = 'las la-star half mr-1'></i>";
        $emptyStar = "<i class = 'las la-star mr-1'></i>";
        $rating = $rating <= $maxRating ? $rating : $maxRating;

        $fullStarCount = (int)$rating;
        $halfStarCount = ceil($rating) - $fullStarCount;
        $emptyStarCount = $maxRating - $fullStarCount - $halfStarCount;

        $html = str_repeat($fullStar, $fullStarCount);
        $html .= str_repeat($halfStar, $halfStarCount);
        $html .= str_repeat($emptyStar, $emptyStarCount);
        echo $html;
    }
}

if (!function_exists('renderStarRatingLatest')) {
    function renderStarRatingLatest($rating, $maxRating = 5)
    {
        $fullStar = "<i class = 'las la-star active fs-14'></i>";
        $halfStar = "<i class = 'las la-star half fs-14'></i>";
        $emptyStar = "<i class = 'las la-star fs-14'></i>";
        $rating = $rating <= $maxRating ? $rating : $maxRating;

        $fullStarCount = (int)$rating;
        $halfStarCount = ceil($rating) - $fullStarCount;
        $emptyStarCount = $maxRating - $fullStarCount - $halfStarCount;

        $html = str_repeat($fullStar, $fullStarCount);
        $html .= str_repeat($halfStar, $halfStarCount);
        $html .= str_repeat($emptyStar, $emptyStarCount);
        echo $html;
    }
}

/**
 * Traductions françaises par défaut pour le site (fallback si absentes en BDD).
 * Clés = lang_key (ex: hot_categories), valeurs = texte en français.
 */
function get_french_translations_fallback()
{
    return [
        'hot_categories' => 'Catégories à la une',
        'featured_categories' => 'Catégories en vedette',
        'categories_catching_eyes_winning_hearts_across_our_marketplace' => 'Catégories qui attirent l\'œil et séduisent sur notre marketplace',
        'all_categories' => 'Toutes les catégories',
        'best_selling' => 'Meilleures ventes',
        'best_sellers' => 'Meilleurs vendeurs',
        'view_all' => 'Voir tout',
        'view_all_sellers' => 'Voir tous les vendeurs',
        'visit_store' => 'Visiter la boutique',
        'todays_deal' => 'Offre du jour',
        'flash_deal' => 'Vente flash',
        'flash_deals' => 'Ventes flash',
        'featured_products' => 'Produits en vedette',
        'new_products' => 'Nouveaux produits',
        'preorder_featured_products' => 'Produits en précommande',
        'products' => 'Produits',
        'wholesale' => 'Grossiste',
        'add_to_wishlist' => 'Ajouter aux favoris',
        'add_to_compare' => 'Ajouter au comparatif',
        'select_variant' => 'Choisir une variante',
        'add_to_cart' => 'Ajouter au panier',
        'select_option' => 'Choisir une option',
        'place_bid' => 'Enchérir',
        'classified_ads' => 'Annonces classées',
        'new' => 'Neuf',
        'used' => 'Occasion',
        'load_more' => 'Charger plus',
        'loading' => 'Chargement...',
        'no_more_products' => 'Plus de produits',
        'error_try_again' => 'Erreur, réessayez',
        'price' => 'Prix',
        'frequently_bought_together' => 'Souvent achetés ensemble',
        'view_cart' => 'Voir le panier',
        'item_successfully_added_to_your_cart' => 'Article ajouté à votre panier !',
        'shipping_info' => 'Informations de livraison',
        'delivery_info' => 'Informations d\'expédition',
        'payment' => 'Paiement',
        'i_agree_to_the' => 'J\'accepte les',
        'terms_and_conditions' => 'conditions générales',
        'return_policy' => 'politique de retour',
        'privacy_policy' => 'politique de confidentialité',
        'return_to_shop' => 'Retour à la boutique',
        'complete_order' => 'Finaliser la commande',
        'you_order_amount_is_less_then_the_minimum_order_amount' => 'Le montant de votre commande est inférieur au minimum.',
        'please_fill_in_all_mandatory_fields' => 'Veuillez remplir tous les champs obligatoires.',
        'you_need_to_agree_with_our_policies' => 'Vous devez accepter nos conditions.',
        'you_need_to_put_transaction_id' => 'Veuillez indiquer l\'identifiant de transaction.',
        'please_login_as_a_customer_to_apply_coupon_code' => 'Connectez-vous en tant que client pour appliquer un code promo.',
        'you_owe_amount_fcfa' => 'Vous devez {amount} FCFA',
        'if_you_dont_have_an_account_you_can_go_to_an_moov_money_agent' => 'Si vous n\'avez pas de compte, vous pouvez vous rendre chez un agent Moov Money.',
        'you_will_receive_an_otp_by_sms_enter_it_below_to_confirm_the_payment' => 'Vous recevrez un OTP par SMS. Saisissez-le ci-dessous pour confirmer le paiement.',
        'you_owe_amount_fcfa_pay_by_orange_money_by_doing' => 'Vous devez {amount} FCFA, payez via Orange Money en faisant :',
        'if_you_dont_have_an_account_you_can_go_to_an_orange_money_agent' => 'Si vous n\'avez pas de compte, vous pouvez vous rendre chez un agent Orange Money.',
        'you_will_receive_an_otp_by_sms_enter_it_and_the_phone_number_below' => 'Vous recevrez un OTP par SMS. Saisissez-le ainsi que le numéro de téléphone ci-dessous.',
        'phone_number_used_for_otp_no_spaces_or_dashes' => 'Numéro de téléphone utilisé pour l\'OTP (sans espaces ni tirets)',
        'please_wait' => 'Veuillez patienter',
        'an_unexpected_error_occurred' => 'Une erreur inattendue s\'est produite',
        'you_will_receive_a_message_with_instructions_to_complete_the_payment_on_your_phone' => 'Vous recevrez un message avec des instructions pour terminer le paiement sur votre téléphone.',
        'your_moov_money_phone_number_no_spaces_or_dashes' => 'Votre numéro Moov Money (sans espaces ni tirets)',
        'otp_code_received_by_sms' => 'Code OTP (reçu par SMS)',
        'send_otp' => 'Envoyer l\'OTP',
        'resend_otp' => 'Renvoyer l\'OTP',
        'initiate_transaction' => 'Initier la transaction',
        'my_cart' => 'Mon panier',
        'shipping_info' => 'Informations de livraison',
        'delivery_info' => 'Informations d\'expédition',
        'confirmation' => 'Confirmation',
        'any_additional_info' => 'Informations complémentaires ?',
        'type_your_text' => 'Saisir votre texte...',
        'select_a_payment_option' => 'Choisir un mode de paiement',
        'cash_on_delivery' => 'Paiement à la livraison',
        'bank_name' => 'Nom de la banque',
        'account_name' => 'Titulaire du compte',
        'account_number' => 'Numéro de compte',
        'routing_number' => 'Code banque',
        'transaction_id' => 'Identifiant de transaction',
        'photo' => 'Photo',
        'browse' => 'Parcourir',
        'choose_image' => 'Choisir une image',
        'or_your_wallet_balance' => 'Ou le solde de votre portefeuille :',
        'insufficient_balance' => 'Solde insuffisant',
        'pay_with_wallet' => 'Payer avec le portefeuille',
        'language_changed_to' => 'Langue changée en',
        'here' => 'Ici',
        'set_hot_categories' => 'Définir les catégories à la une',
        // Header / navigation
        'become_a_seller' => 'Devenir vendeur',
        'login_to_seller' => 'Espace vendeur',
        'helpline' => 'Assistance',
        'i_am_shopping_for' => 'Je recherche...',
        'notifications' => 'Notifications',
        'notification' => 'Notification',
        'no_notification_found' => 'Aucune notification',
        'view_all_notifications' => 'Voir toutes les notifications',
        'avatar' => 'Avatar',
        'login' => 'Connexion',
        'registration' => 'Inscription',
        'dashboard' => 'Tableau de bord',
        'purchase_history' => 'Historique des commandes',
        'preorder_list' => 'Précommandes',
        'preorder' => 'Précommande',
        'preorder_conversations' => 'Conversations précommandes',
        'downloads' => 'Téléchargements',
        'conversations' => 'Messages',
        'my_wallet' => 'Mon portefeuille',
        'support_ticket' => 'Support',
        'logout' => 'Déconnexion',
        'sign_out' => 'Déconnexion',
        'categories' => 'Catégories',
        'see_all' => 'Tout voir',
        'my_account' => 'Mon compte',
        'wishlist' => 'Favoris',
        'compare' => 'Comparer',
        'top_banner' => 'Bannière',
        // Footer
        'last_viewed_products' => 'Derniers produits vus',
        'terms_conditions' => 'Conditions générales',
        'return_policy' => 'Politique de retour',
        'support_policy' => 'Politique d\'assistance',
        'privacy_policy' => 'Politique de confidentialité',
        'subscribe_to_our_newsletter_for_regular_updates_about_offers_coupons_more' => 'Inscrivez-vous à notre newsletter pour les offres, bons de réduction et plus.',
        'your_email_address' => 'Votre adresse e-mail',
        'subscribe' => 'S\'inscrire',
        'follow_us' => 'Suivez-nous',
        'mobile_apps' => 'Applications mobiles',
        'contacts' => 'Contact',
        // Compte / profil
        'manage_profile' => 'Gérer mon profil',
        'delete_my_account' => 'Supprimer mon compte',
        'refund_requests' => 'Demandes de remboursement',
        'followed_sellers' => 'Vendeurs suivis',
        'classified_products' => 'Annonces classées',
        'auction' => 'Enchères',
        'bidded_products' => 'Mes enchères',
        'earning_points' => 'Points de fidélité',
        'affiliate' => 'Programme partenaire',
        'affiliate_system' => 'Programme partenaire',
        'payment_history' => 'Historique des paiements',
        'withdraw_request_history' => 'Historique des retraits',
        // Avis et évaluations produit
        'reviews_ratings' => 'Avis et évaluations',
        'total_review' => 'Total des avis',
        'rate_this_product' => 'Évaluer ce produit',
        'no_reviews_found' => 'Aucun avis trouvé !',
        'message_us' => 'Nous contacter',
        'review' => 'Avis',
        'rating' => 'Note',
        'your_review' => 'Votre avis',
        'review_images' => 'Images de l\'avis',
        'submit_review' => 'Envoyer l\'avis',
        'reviews' => 'Avis',
        'ratings' => 'Évaluations',
        'your_reviews_ratings' => 'Vos avis et évaluations',
        'these_images_are_visible_in_product_review_page_gallery_upload_square_images' => 'Ces images sont visibles dans la galerie des avis. Privilégiez des images carrées.',
        'filter_by_star_rating' => 'Filtrer par note',
        // Page détail produit
        'products_from_this_seller' => 'Produits de ce vendeur',
        'more_from_this_seller' => 'Plus de ce vendeur',
        'no_frequently_bought_products_found' => 'Aucun produit « achetés ensemble » trouvé !',
        'related_products' => 'Produits similaires',
        'frequently_bought' => 'Souvent achetés ensemble',
        'frequently_bought_together' => 'Souvent achetés ensemble',
        'frequently_bought_products' => 'Souvent achetés ensemble',
        'pricing' => 'Tarifs',
        'exclusive_for_today_only' => 'Exclusif pour aujourd\'hui uniquement',
        'minimum_order_qty' => 'Quantité minimale',
        'order_via_whatsapp' => 'Commander via WhatsApp',
        'products_from_this_brand' => 'Produits de cette marque',
        'cash_on_delivery_available' => 'Paiement à la livraison disponible',
        'home' => 'Accueil',
        'description' => 'Description',
        'product_queries' => 'Questions sur le produit',
        'any_query_about_this_product' => 'Une question sur ce produit ?',
        'product_name' => 'Nom du produit',
        'your_question' => 'Votre question',
        'cancel' => 'Annuler',
        'send' => 'Envoyer',
        'bid_for_product' => 'Enchérir sur ce produit',
        'min_bid_amount' => 'Montant minimum :',
        'place_bid_price' => 'Prix de l\'enchère',
        'enter_amount' => 'Saisir le montant',
        'an' => 'Une',
        'gst_will_be_applied_if_you_win_the_bid_and_proceed_with_the_purchase' => 'TVA sera appliquée si vous remportez l\'enchère et poursuivez l\'achat',
        'submit' => 'Envoyer',
        'warranty_note' => 'Note de garantie',
        'refund_note' => 'Note de remboursement',
        'share_with_friends' => 'Partager avec des amis',
        'trading_is_more_effective_when_you_share_products_with_friends' => 'Le partage rend les achats plus utiles !',
        'share_you_link' => 'Partager votre lien',
        'copied' => 'Copié',
        'link_copied_to_clipboard' => 'Lien copié dans le presse-papiers',
        'oops_unable_to_copy' => 'Impossible de copier',
        'sku_copied_to_clipboard' => 'Réf. copiée dans le presse-papiers',
        'share_to' => 'Partager sur',
        // Page catégorie / listing (category/motos, etc.)
        'filters' => 'Filtres',
        'price_range' => 'Fourchette de prix',
        'filter_by_color' => 'Filtrer par couleur',
        'filter_by_availability' => 'Filtrer par disponibilité',
        'available_now' => 'Disponible',
        'upcoming' => 'À venir',
        'all' => 'Tout',
        'general_products' => 'Produits classiques',
        'preorder_products' => 'Produits en précommande',
        'brand' => 'Marque',
        'showing_results' => 'Résultats',
        'search_result_for' => 'Résultats pour',
        'sort_by' => 'Trier par',
        'newest' => 'Plus récents',
        'oldest' => 'Plus anciens',
        'price_low_to_high' => 'Prix croissant',
        'price_high_to_low' => 'Prix décroissant',
        'see_more' => 'Voir plus',
        'see_less' => 'Voir moins',

        // Back-office / Admin (sidebar, dashboard, produits, ventes, notes, formulaires)
        'add_more' => 'Ajouter',
        'language_settings_instructions' => 'Instructions de configuration des langues',
        'translation_settings_instructions' => 'Instructions de traduction',
        'add_new_language' => 'Ajouter une langue',
        'enter_the' => 'Saisir',
        'language_name' => 'Nom de la langue',
        'language_code' => 'Code de la langue',
        'short_form' => 'forme courte',
        'flutter_app_lang_code' => 'Code langue app Flutter',
        'system_default_language' => 'Langue par défaut du système',
        'import' => 'Importer',
        'translation' => 'Traduction',
        'click' => 'Cliquer',
        'save' => 'Enregistrer',
        'translation_settings_instructions' => 'Instructions de traduction',
        'click_the' => 'Cliquez sur',
        'option_next_to_the_language_you_want_to_edit' => 'option « Traduction » à côté de la langue à modifier.',
        'on_the_translation_page' => 'Sur la page Traduction, le bouton',
        'button_to_automatically_translate_your_website_content' => 'permet de traduire automatiquement le contenu du site.',
        'to_sync_translations_for_the_mobile_app_click' => 'Pour synchroniser les traductions pour l\'app mobile, cliquez sur',
        'sync_translation_for_app' => 'Synchroniser la traduction pour l\'app',
        'to_export_the_translation_file_click' => 'Pour exporter le fichier de traduction, cliquez sur',
        'export_arb_file' => 'Exporter le fichier arb',
        'to_manually_update_any_translation_value_edit_the_value_fields_and_click' => 'Pour modifier manuellement une traduction, éditez les champs et cliquez sur',
        'to_copy_all_keys_into_the_value_fields_click' => 'Pour copier toutes les clés dans les champs valeur, cliquez sur',
        'copy_translations' => 'Copier les traductions',
        'then_click_save' => 'puis Enregistrer.',
        'google_translate_wont_override_your_custom_translations' => 'Google Traduction ne remplace pas vos traductions personnalisées.',
        'dashboard' => 'Tableau de bord',
        'products' => 'Produits',
        'orders' => 'Commandes',
        'sales' => 'Ventes',
        'notes' => 'Notes',
        'customers' => 'Clients',
        'reports' => 'Rapports',
        'settings' => 'Paramètres',
        'add' => 'Ajouter',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'search' => 'Rechercher',
        'filter' => 'Filtrer',
        'export' => 'Exporter',
        'import' => 'Importer',
        'submit' => 'Envoyer',
        'cancel' => 'Annuler',
        'back' => 'Retour',
        'next' => 'Suivant',
        'previous' => 'Précédent',
        'loading' => 'Chargement',
        'no_data_available' => 'Aucune donnée disponible',
        'actions' => 'Actions',
        'status' => 'Statut',
        'date' => 'Date',
        'name' => 'Nom',
        'description' => 'Description',
        'price' => 'Prix',
        'quantity' => 'Quantité',
        'total' => 'Total',
        'view' => 'Voir',
        'view_all' => 'Voir tout',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'yes' => 'Oui',
        'no' => 'Non',
        'confirm' => 'Confirmer',
        'are_you_sure' => 'Êtes-vous sûr ?',
        // reCAPTCHA page
        'how_to_interpret_the_recaptcha_v3_scores' => 'Comment interpréter les scores reCAPTCHA V3',
        'very_likely_a_bot' => 'Très probablement un bot',
        'suspicious_activity' => 'Activité suspecte',
        'possibly_human' => 'Probablement humain',
        'likely_human' => 'Probablement humain',
        'very_likely_human' => 'Très probablement humain',
        'recommended_action_block_the_request_or_require_additional_verification' => 'Action recommandée : Bloquer la requête ou exiger une vérification supplémentaire.',
        'recommended_action_might_want_to_require_additional_verification' => 'Action recommandée : Envisager une vérification supplémentaire.',
        'recommended_action_could_be_legitimate_traffic' => 'Action recommandée : Peut être du trafic légitime.',
        'recommended_action_probably_safe_to_allow' => 'Action recommandée : Probablement sans danger.',
        'recommended_action_definitely_safe_to_allow' => 'Action recommandée : Certainement sans danger.',
        'if_google_recaptcha_v3_credentials_have_not_yet_been_created_please_register_your_site_by_visiting' => 'Si les identifiants Google reCAPTCHA v3 n\'ont pas encore été créés, veuillez enregistrer votre site en visitant',
        'this_link' => 'ce lien',
        'and_complete_the_setup_process' => 'et terminez le processus de configuration.',
        'recaptcha_applicable_pages' => 'Pages concernées par le reCAPTCHA',
        'are_you_sure_you_want_to_change_the_recaptcha_setting_for' => 'Êtes-vous sûr de vouloir modifier le paramètre reCAPTCHA pour',
        'greater_than_or_equal_to_03' => 'Supérieur ou égal à 0.3',
        'greater_than_or_equal_to_05' => 'Supérieur ou égal à 0.5',
        'greater_than_or_equal_to_07' => 'Supérieur ou égal à 0.7',
        'greater_than_or_equal_to_09' => 'Supérieur ou égal à 0.9',
        'something_went_wrong' => 'Une erreur est survenue',
        'admin_login' => 'Connexion Admin',
        'customer_login' => 'Connexion client',
        'customer_registration' => 'Inscription client',
        'seller_login' => 'Connexion vendeur',
        'seller_registration' => 'Inscription vendeur',
        'seller_mail_verification' => 'Vérification email vendeur',
        'delivery_boy_login' => 'Connexion livreur',
        'contact_us_form' => 'Formulaire de contact',
        'success' => 'Succès',
        'error' => 'Erreur',
        'warning' => 'Attention',
        'info' => 'Info',
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'created_by' => 'Créé par',
        'select' => 'Sélectionner',
        'select_all' => 'Tout sélectionner',
        'none' => 'Aucun',
        'all' => 'Tout',
        'other' => 'Autre',
        'optional' => 'Optionnel',
        'required' => 'Requis',

        // Sidebar & menu back-office
        'search_in_menu' => 'Rechercher dans le menu',
        'pos_system' => 'Système PDV',
        'pos_manager' => 'Gestion PDV',
        'pos_configuration' => 'Configuration PDV',
        'add_new_product' => 'Ajouter un produit',
        'all_products' => 'Tous les produits',
        'in_house_products' => 'Produits maison',
        'add_new_digital_product' => 'Ajouter un produit numérique',
        'seller_product' => 'Produits vendeur',
        'physical_products' => 'Produits physiques',
        'digital_products' => 'Produits numériques',
        'bulk_import' => 'Import en masse',
        'bulk_export' => 'Export en masse',
        'category' => 'Catégorie',
        'category_based_discount' => 'Réduction par catégorie',
        'all_brands' => 'Toutes les marques',
        'brand_bulk_import' => 'Import marques en masse',
        'custom_label' => 'Libellé personnalisé',
        'attribute' => 'Attribut',
        'colors' => 'Couleurs',
        'size_guide' => 'Guide des tailles',
        'size_chart' => 'Tableau des tailles',
        'measurement_points' => 'Points de mesure',
        'warranty' => 'Garantie',
        'smart_bar' => 'Barre intelligente',
        'product_reviews' => 'Avis produits',
        'preorder' => 'Précommande',
        'add_new_preorder_products' => 'Ajouter des produits en précommande',
        'preorder_products' => 'Produits en précommande',
        'orders_preorder' => 'Commandes (précommande)',
        'all_orders' => 'Toutes les commandes',
        'inhouse_orders' => 'Commandes maison',
        'seller_orders' => 'Commandes vendeurs',
        'delayed_prepayment' => 'Paiement différé',
        'delayed_final_orders' => 'Commandes finales différées',
        'preorder_commission_history' => 'Historique commission précommande',
        'preorder_settings' => 'Paramètres précommande',
        'preorder_product_conversation' => 'Conversations produits précommande',
        'preorder_product_queries' => 'Questions produits précommande',
        'preorder_product_reviews' => 'Avis produits précommande',
        'faqs' => 'FAQ',
        'preorder_notification_types' => 'Types de notification précommande',
        'add_new_note' => 'Ajouter une note',
        'note_list' => 'Liste des notes',
        'auction_products' => 'Produits aux enchères',
        'add_new_auction_product' => 'Ajouter un produit aux enchères',
        'all_auction_products' => 'Tous les produits aux enchères',
        'inhouse_auction_products' => 'Produits aux enchères maison',
        'seller_auction_products' => 'Produits aux enchères vendeurs',
        'auction_products_orders' => 'Commandes produits aux enchères',
        'wholesale_products' => 'Produits en gros',
        'add_new_wholesale_product' => 'Ajouter un produit en gros',
        'all_wholesale_products' => 'Tous les produits en gros',
        'in_house_wholesale_products' => 'Produits en gros maison',
        'seller_wholesale_products' => 'Produits en gros vendeurs',
        'pickup_point_orders' => 'Commandes point de retrait',
        'pick_point_orders' => 'Commandes point de retrait',
        'unpaid_orders' => 'Commandes impayées',
        'delivery_boy' => 'Livreur',
        'all_delivery_boy' => 'Tous les livreurs',
        'add_delivery_boy' => 'Ajouter un livreur',
        'payment_histories' => 'Historique des paiements',
        'collected_histories' => 'Historique des encaissements',
        'cancel_request' => 'Demandes d\'annulation',
        'configuration' => 'Configuration',
        'refunds' => 'Remboursements',
        'refund_requests' => 'Demandes de remboursement',
        'approved_refunds' => 'Remboursements approuvés',
        'rejected_refunds' => 'Remboursements rejetés',
        'refund_configuration' => 'Configuration remboursements',
        'category_based_refund' => 'Remboursement par catégorie',
        'customer_list' => 'Liste des clients',
        'unverified_customers' => 'Clients non vérifiés',
        'classified_products' => 'Produits classés',
        'classified_packages' => 'Forfaits classés',
        'all_seller' => 'Tous les vendeurs',
        'applied_seller' => 'Vendeurs candidats',
        'seller_rating_followers' => 'Notes et abonnés vendeurs',
        'payouts' => 'Paiements',
        'payout_requests' => 'Demandes de paiement',
        'seller_commission' => 'Commission vendeur',

        // Formulaires & pages
        'edit_seller_information' => 'Modifier les informations vendeur',
        'seller_information' => 'Informations vendeur',
        'email_address' => 'Adresse e-mail',
        'password' => 'Mot de passe',
        'collection_from_delivery_boy' => 'Encaissement auprès du livreur',
        'deliver_boy' => 'Livreur',
        'total_payable' => 'Total à payer',
        'paid_amount' => 'Montant payé',
        'paid' => 'Payé',
        'google_login_credential' => 'Identifiants Google',
        'client_id' => 'ID client',
        'client_secret' => 'Secret client',
        'google_client_id' => 'ID client Google',
        'google_client_secret' => 'Secret client Google',
        'facebook_login_credential' => 'Identifiants Facebook',
        'app_id' => 'ID application',
        'facebook_client_id' => 'ID client Facebook',
        'app_secret' => 'Secret application',
        'facebook_client_secret' => 'Secret client Facebook',
        'twitter_login_credential' => 'Identifiants Twitter',
        'twitter_client_id' => 'ID client Twitter',
        'twitter_client_secret' => 'Secret client Twitter',
        'apple_login_credential' => 'Identifiants Apple',
        'callback_url' => 'URL de rappel',
        'apple_client_id' => 'ID client Apple',
        'apple_client_secret' => 'Secret client Apple',
        'image' => 'Image',
        'uploaded_by' => 'Publié par',
        'customer_status' => 'Statut client',
        'published' => 'Publié',
        'options' => 'Options',
        'product_image' => 'Image produit',
        'unpublished' => 'Non publié',
        'show' => 'Afficher',
        'data_can_not_change_in_demo_mode' => 'Les données ne peuvent pas être modifiées en mode démo.',
        'published_products_updated_successfully' => 'Produits publiés mis à jour avec succès.',
        'something_went_wrong' => 'Une erreur s\'est produite.',
        'earning_report' => 'Rapport des gains',
        'total_sales_alltime' => 'Ventes totales (tout temps)',
        'sales_this_month' => 'Ventes ce mois',
        'payouts_this_month' => 'Paiements ce mois',
        'total_category' => 'Total catégories',
        'not_found' => 'Non trouvé',
        'total_brands' => 'Total marques',
        'top_brands' => 'Meilleures marques',
        'net_sales' => 'Ventes nettes',
        'by_sale_category' => 'Par catégorie de vente',
        'today' => 'Aujourd\'hui',
        'week' => 'Semaine',
        'month' => 'Mois',
        'by_expense_category' => 'Par catégorie de dépense',
        'sale_analytics' => 'Analytique des ventes',
        'payouts_analytics' => 'Analytique des paiements',
        'product_sales' => 'Ventes produits',
        'commission' => 'Commission',
        'seller_subscription' => 'Abonnement vendeur',
        'customer_subscription' => 'Abonnement client',
        'delivery' => 'Livraison',
        'seller_payout' => 'Paiement vendeur',
        'product_refund' => 'Remboursement produit',
        'coupon_information_update' => 'Mise à jour du bon',
        'coupon_type' => 'Type de bon',
        'for_products' => 'Pour produits',
        'for_total_orders' => 'Pour commandes totales',
        'welcome_coupon' => 'Bon de bienvenue',
        'attribute_information' => 'Informations attribut',
        'attribute_name' => 'Nom de l\'attribut',
        'attribute_value' => 'Valeur de l\'attribut',
        'enter_attribute_value' => 'Saisir la valeur',
        'brand_information' => 'Informations marque',
        'translatable' => 'Traduisible',
        'logo' => 'Logo',
        '120x80' => '120x80',
        'browse' => 'Parcourir',
        'choose_file' => 'Choisir un fichier',
        'minimum_dimensions_required_120px_width_x_80px_height' => 'Dimensions minimales : 120px × 80px.',
        'meta_title' => 'Meta titre',
        'meta_description' => 'Meta description',
        'meta_keywords' => 'Meta mots-clés',
        'separate_with_coma' => 'Séparer par des virgules',
        'slug' => 'Slug',
        'authentication_page_layout' => 'Mise en page des pages d\'authentification',
        'authentication_layout_1_boxed' => 'Mise en page 1 - Encadrée',
        'authentication_layout_2_free' => 'Mise en page 2 - Libre',
        'authentication_layout_3_focused' => 'Mise en page 3 - Centrée',
        'configure_your_authentication_page_layout' => 'Configurez la mise en page de vos pages d\'authentification',
        'each_page_contain_different_layout_choose_one_to_bundle_it_in_your_layout' => 'Chaque page a une mise en page différente, choisissez-en une.',
        'authentication_page_images' => 'Images des pages d\'authentification',
        'admin_login_page_image' => 'Image page connexion admin',
        'choose_files' => 'Choisir des fichiers',
        'minimum_dimensions_required_960px_width_x_911px_height' => 'Dimensions minimales : 960px × 911px.',
        'customer_login_page_image' => 'Image page connexion client',
        'custom_product_visitiors' => 'Visiteurs produits personnalisés',
        'show_custom_product_visitors' => 'Afficher les visiteurs personnalisés',
        'visitors_range' => 'Plage de visiteurs',
        'min_visitors' => 'Visiteurs min',
        'to' => 'à',
        'max_visitors' => 'Visiteurs max',
        'minimum_visitors_cannot_be_greater_than_maximum_visitors' => 'Le minimum ne peut pas être supérieur au maximum.',

        // Page Langues
        'default_language' => 'Langue par défaut',
        'import_app_translations' => 'Importer les traductions app',
        'english_trasnlation_file' => 'Fichier de traduction anglais',
        'choose_app_enarb_file' => 'Choisir le fichier app_en.arb',
        'to_create_a_new_language_click' => 'Pour créer une nouvelle langue, cliquez sur',
        'and' => 'et',
        'flutter_app_lang_code' => 'Code langue app Flutter',
        'the_page_will_redirect_to_the_language_listing_page' => 'La page redirigera vers la liste des langues.',
        'you_can_select_any_language_from_the_list_as_the' => 'Vous pouvez choisir une langue dans la liste comme',
        'and_click_save' => 'puis Enregistrer.',
        'to_import_app_translation_files_select_the_file_and_click' => 'Pour importer les fichiers de traduction, sélectionnez le fichier et cliquez sur',
        'option_next_to_the_language_you_want_to_edit' => 'option à côté de la langue à modifier.',
        'language' => 'Langue',
        'code' => 'Code',
        'rtl' => 'RTL',

        // Fichiers, rapports, notifications, marketing
        'upload_new_file' => 'Téléverser un fichier',
        'back_to_uploaded_files' => 'Retour aux fichiers',
        'drag_drop_your_files' => 'Glissez-déposez vos fichiers',
        'commission_history_report' => 'Rapport historique des commissions',
        'all_notification_types' => 'Tous les types de notification',
        'default_notification_types_can_not_be_deleted' => 'Les types de notification par défaut ne peuvent pas être supprimés.',
        'customer' => 'Client',
        'seller' => 'Vendeur',
        'admin' => 'Admin',
        'bulk_action' => 'Action groupée',
        'delete_selection' => 'Supprimer la sélection',
        'type_enter' => 'Saisir et valider',
        'type' => 'Type',
        'default_text' => 'Texte par défaut',
        'add_new_notification_type' => 'Ajouter un type de notification',
        'notification_type' => 'Type de notification',
        'best_within_80_character' => 'Idéalement 80 caractères',
        'nb_use_character_number_only' => 'N.B. : Utiliser uniquement lettres et chiffres',
        'notification_type_status_updated_successfully' => 'Statut du type de notification mis à jour.',
        'notification_types_deleted_successfully' => 'Types de notification supprimés.',
        'flash_deal_information' => 'Informations offre flash',
        'title' => 'Titre',
        'background_color' => 'Couleur de fond',
        'hexacode' => 'Code hexadécimal',
        'text_color' => 'Couleur du texte',
        'white' => 'Blanc',
        'dark' => 'Sombre',
        'banner' => 'Bannière',
        'this_image_is_shown_as_cover_banner_in_flash_deal_details_page_minimum_dimensions_required_436px_width_x_443px_height' => 'Cette image est affichée en bannière. Dimensions minimales : 436px × 443px.',
        'select_date' => 'Choisir la date',
        'choose_products' => 'Choisir les produits',
        'if_any_product_has_discount_or_exists_in_another_flash_deal_the_discount_will_be_replaced_by_this_discount_time_limit' => 'Si un produit a déjà une réduction ou est dans une autre offre flash, celle-ci sera remplacée.',
        'add_new_brand' => 'Ajouter une marque',
        'search_brands' => 'Rechercher des marques…',
        'selected_item_deleted_successfully' => 'Élément(s) supprimé(s).',
        'please_select_at_least_one_brand' => 'Veuillez sélectionner au moins une marque.',
        'delete_confirmation' => 'Confirmation de suppression',
        'are_you_sure_you_want_to_delete_the_selected_brands' => 'Voulez-vous vraiment supprimer les marques sélectionnées ?',
        'associated_products_will_be_affected_once_deleted_the_brands_and_products_will_be_permanently_removed' => 'Les produits associés seront impactés. La suppression est définitive.',
        'are_you_sure_you_want_to_delete_the_selected_brand' => 'Voulez-vous vraiment supprimer cette marque ?',
        'associated_products_will_be_affected_once_deleted_the_brand_and_product_will_be_permanently_removed' => 'Les produits associés seront impactés. La suppression est définitive.',
        'failed_to_load_data' => 'Échec du chargement des données.',
        'failed_to_load_brand_details_data' => 'Échec du chargement des détails de la marque.',

        'seller_based_commission' => 'Commission par vendeur',
        'category_based_commission' => 'Commission par catégorie',
        'seller_packages' => 'Forfaits vendeurs',
        'seller_verification_form' => 'Formulaire de vérification vendeur',
        'uploaded_files' => 'Fichiers téléversés',
        'in_house_product_sale' => 'Ventes produits maison',
        'seller_products_sale' => 'Ventes produits vendeurs',
        'products_stock' => 'Stock produits',
        'products_wishlist' => 'Listes de souhaits',
        'user_searches' => 'Recherches utilisateurs',
        'commission_history' => 'Historique des commissions',
        'wallet_recharge_history' => 'Historique recharges portefeuille',
        'blog_system' => 'Blog',
        'all_posts' => 'Tous les articles',
        'categories' => 'Catégories',

        // Tableau de bord (Dashboard)
        'total_customer' => 'Total clients',
        'top_customers' => 'Meilleurs clients',
        'total_products' => 'Total produits',
        'in_house_products' => 'Produits maison',
        'sellers_products' => 'Produits vendeurs',
        'total_category' => 'Total catégories',
        'total_brands' => 'Total marques',
        'top_brands' => 'Meilleures marques',
        'total_sales' => 'Total ventes',
        'sales_stat' => 'Statistiques ventes',
        'inhouse_sales' => 'Ventes maison',
        'sellers_sales' => 'Ventes vendeurs',
        'total_sellers' => 'Total vendeurs',
        'approved_sellers' => 'Vendeurs approuvés',
        'pending_seller' => 'Vendeur en attente',
        'top_sellers' => 'Meilleurs vendeurs',
        'all_sellers' => 'Tous les vendeurs',
        'pending_sellers' => 'Vendeurs en attente',
        'activate_vendor_system' => 'Activer le système vendeurs',
        'total_order' => 'Total commandes',
        'pending_order' => 'Commandes en attente',
        'order_placed' => 'Commandes passées',
        'confirmed_order' => 'Commandes confirmées',
        'processed_order' => 'Commandes traitées',
        'order_shipped' => 'Commandes expédiées',
        'inhouse_top_category' => 'Meilleures catégories maison',
        'by_sales' => 'Par ventes',
        'inhouse_store' => 'Boutique maison',
        'all_inhouse_orders' => 'Toutes les commandes maison',
        'inhouse_product' => 'Produits maison',
        'ratings' => 'Notes',
        'top_seller_products' => 'Meilleurs vendeurs et produits',
        'yearly_sales' => 'Ventes annuelles',
        'inhouse_top_brands' => 'Meilleures marques maison',
        'item' => 'Article',
        'quantity' => 'Quantité',
        'total_price' => 'Prix total',
        'sellers' => 'Vendeurs',
        'brand' => 'Marque',
        'brands' => 'Marques',
        'please_configure_smtp_setting_to_work_all_email_sending_functionality' => 'Veuillez configurer SMTP pour activer l\'envoi d\'e-mails.',
        'configure_now' => 'Configurer maintenant',

        // Sidebar & menu complémentaire
        'products' => 'Produits',
        'orders' => 'Commandes',
        'refund_requests' => 'Demandes de remboursement',
        'delivery_boys' => 'Livreurs',
        'all_delivery_boys' => 'Tous les livreurs',
        'coupon' => 'Bon de réduction',
        'conversations' => 'Conversations',
        'reviews' => 'Avis',
        'product_reviews' => 'Avis produits',
        'product_queries' => 'Questions produits',
        'preorder_product_queries' => 'Questions produits précommande',
        'preorder_product_reviews' => 'Avis produits précommande',
        'marketing' => 'Marketing',
        'support' => 'Support',
        'website_setup' => 'Configuration du site',
        'setup_configurations' => 'Paramètres et configurations',
        'staffs' => 'Personnel',
        'product_conversations' => 'Conversations produits',
        'flash_deals' => 'Offres flash',
        'dynamic_popup' => 'Popup dynamique',
        'custom_alert' => 'Alerte personnalisée',
        'custom_sell_alert' => 'Alerte vente personnalisée',
        'email_templates' => 'Modèles d\'e-mails',
        'admin_templates' => 'Modèles admin',
        'seller_templates' => 'Modèles vendeur',
        'customer_templates' => 'Modèles client',
        'common_templates' => 'Modèles communs',
        'newsletters' => 'Newsletters',
        'notification' => 'Notification',
        'notification_types' => 'Types de notification',
        'custom_notification' => 'Notification personnalisée',
        'custom_notification_history' => 'Historique notifications personnalisées',
        'bulk_sms' => 'SMS groupés',
        'subscribers' => 'Abonnés',
        'custom_visitors' => 'Visiteurs personnalisés',
        'ticket' => 'Ticket',
        'support_desk' => 'Support',
        'reports' => 'Rapports',
        'multivendor' => 'Multivendeur',
        'browse_website' => 'Voir le site',
        'clear_cache' => 'Vider le cache',
        'add_new' => 'Ajouter',
        'new_product' => 'Nouveau produit',
        'new_category' => 'Nouvelle catégorie',
        'new_brand' => 'Nouvelle marque',
        'homepage_settings' => 'Paramètres page d\'accueil',
        'earnings' => 'Gains',
        'preorders' => 'Précommandes',
        'pos' => 'PDV',
        'attributes' => 'Attributs',
        'product_bulk_upload' => 'Import produits en masse',
        'refund_request' => 'Demande de remboursement',
        'offline_payment_methods' => 'Paiements hors ligne',
        'delivery_configuration' => 'Configuration livraison',
        'system' => 'Système',
        'my_profile' => 'Mon profil',
        'logout' => 'Déconnexion',
        'product_category' => 'Catégorie de produit',
        'view_more' => 'Voir plus',
        'unit_price' => 'Prix unitaire',
        'num_of_sale' => 'Nb ventes',
        'num_of_sale_high_low' => 'Nb ventes (décroissant)',
        'num_of_sale_low_high' => 'Nb ventes (croissant)',
        'payment_status' => 'Statut de paiement',
        'filter_by_payment_status' => 'Filtrer par statut de paiement',
        'cash_on_delivery' => 'Paiement à la livraison',
        'cash_on_delivery_option_is_disabled_activate_this_feature_from_here' => 'Le paiement à la livraison est désactivé. Activez cette option ici.',

        // Coris Money labels
        'coris' => 'Coris Money',
        'coris_money_client_id' => 'Identifiant client Coris Money',
        'coris_money_client_secret' => 'Secret client Coris Money',
        'coris_money_shop_code_code_pv' => 'Code point de vente Coris Money (Code PV)',
        'coris_money_test_base_url' => 'URL de base de test Coris Money',
        'coris_money_live_base_url' => 'URL de base de production Coris Money',
        'coris_money_sandbox_mode' => 'Mode sandbox Coris Money',
        // Formulaire de paiement Coris Money (frontend)
        'pay_by' => 'Payer par',
        'to_pay_with_coris_money_open_your_corismoney_client_app_and_initiate_an_internet_payment_you_will_receive_a_withdrawal_code_that_you_must_enter_below_with_your_phone_number' => 'Pour payer avec Coris Money, ouvrez l\'application CorisMoney et initiez un paiement internet. Vous recevrez un code de retrait à saisir ci-dessous avec votre numéro de téléphone.',
        'coris_money_phone_number' => 'Numéro de téléphone Coris Money',
        'phone_number_linked_to_coris_money_account' => 'Numéro lié à votre compte Coris Money',
        'withdrawal_code_coderetrait' => 'Code de retrait (codeRetrait)',
        'code_provided_by_corismoney_app' => 'Code fourni par l\'application CorisMoney',
        'confirm_payment' => 'Confirmer le paiement',
        'payment_failed' => 'Paiement échoué',
        'an_unexpected_error_occurred_please_try_again' => 'Une erreur inattendue s\'est produite. Veuillez réessayer.',

        // Messages API - Adresses
        'address_is_saved' => 'Adresse enregistrée',
        'address_not_found' => 'Adresse introuvable',
        'delivery_address_is_saved' => 'Adresse de livraison enregistrée',
        'could_not_save_the_address' => 'Impossible d\'enregistrer l\'adresse',
        'shipping_information_has_been_added_successfully' => 'Informations de livraison ajoutées avec succès',
        'shipping_information_has_been_updated_successfully' => 'Informations de livraison mises à jour avec succès',
        'shipping_information_has_been_deleted' => 'Informations de livraison supprimées',
        'shipping_location_in_map_updated_successfully' => 'Localisation mise à jour avec succès',
        'default_shipping_information_has_been_updated' => 'Adresse de livraison par défaut mise à jour',
        'shipping_info_saved' => 'Informations de livraison enregistrées.',
        'please_add_shipping_address' => 'Veuillez ajouter une adresse de livraison',
        'please_login_first' => 'Veuillez d\'abord vous connecter.',

        // Messages API - Authentification & Compte
        'successfully_logged_in' => 'Connexion réussie',
        'successfully_logged_out' => 'Déconnexion réussie',
        'login_failed' => 'Échec de la connexion',
        'user_is_not_found' => 'Utilisateur introuvable',
        'user_not_found' => 'Utilisateur introuvable',
        'no_user_is_found' => 'Aucun utilisateur trouvé',
        'user_is_banned' => 'Votre compte est suspendu',
        'unauthenticated_user' => 'Utilisateur non authentifié.',
        'unauthorized' => 'Non autorisé',
        'your_account_is_now_verified' => 'Votre compte est maintenant vérifié',
        'your_account_deletion_successfully_done' => 'Votre compte a été supprimé avec succès',
        'your_password_is_resetplease_login' => 'Votre mot de passe a été réinitialisé. Veuillez vous connecter.',
        'profile_information_has_been_updated_successfully' => 'Profil mis à jour avec succès',
        'customer_updated_successfully' => 'Client mis à jour avec succès',
        'your_seller_account_is_under_review_we_will_notify_you_once_approved' => 'Votre compte vendeur est en cours de vérification. Nous vous informerons une fois approuvé.',
        'your_shop_verification_request_has_been_submitted_successfully' => 'Votre demande de vérification de boutique a été soumise avec succès !',
        'no_social_account_matches' => 'Aucun compte social correspondant',
        'no_social_provider_matches' => 'Aucun fournisseur social correspondant',

        // Messages API - Code OTP & Vérification
        'a_code_is_sent' => 'Un code a été envoyé',
        'a_code_is_sent_again' => 'Un nouveau code a été envoyé',
        'verification_code_is_sent_again' => 'Code de vérification renvoyé',
        'code_does_not_match_you_can_request_for_resending_the_code' => 'Code incorrect. Vous pouvez demander un renvoi du code.',
        'otp_sent_successfully_please_check_your_phone' => 'OTP envoyé avec succès. Vérifiez votre téléphone.',
        'failed_to_send_otp_please_try_again' => 'Échec de l\'envoi de l\'OTP. Veuillez réessayer.',
        'password_confirmation_does_not_match' => 'La confirmation du mot de passe ne correspond pas',
        'minimum_6_digits_required_for_password' => '6 caractères minimum requis pour le mot de passe',
        'name_is_required' => 'Le nom est requis',
        'email_is_required' => 'L\'email est requis',
        'email_must_be_a_valid_email_address' => 'L\'email doit être une adresse valide',
        'phone_is_required' => 'Le téléphone est requis',
        'phone_must_be_a_number' => 'Le téléphone doit être un nombre.',
        'phone_number_is_not_valid' => 'Numéro de téléphone invalide',
        'the_email_has_already_been_taken' => 'Cet email est déjà utilisé',
        'the_phone_has_already_been_taken' => 'Ce numéro est déjà utilisé',
        'password_is_required' => 'Le mot de passe est requis',

        // Messages API - Panier & Commandes
        'product_added_to_cart_successfully' => 'Produit ajouté au panier avec succès',
        'cart_updated' => 'Panier mis à jour',
        'cart_is_empty' => 'Le panier est vide',
        'cart_is_Empty' => 'Le panier est vide',
        'cart_has_been_deleted_successfully' => 'Panier supprimé avec succès',
        'cart_status_updated_successfully' => 'Statut du panier mis à jour avec succès',
        'product_is_successfully_removed_from_your_cart' => 'Produit retiré du panier',
        'already_added_this_product' => 'Ce produit est déjà dans le panier',
        'maximum_available_quantity_reached' => 'Quantité maximale disponible atteinte',
        'the_requested_quantity_is_not_available_for_' => 'La quantité demandée n\'est pas disponible pour ',
        'is_stock_out' => 'est en rupture de stock.',
        'out_of_stock' => 'En rupture de stock',
        'in_stock' => 'En stock',
        'your_order_has_been_placed_successfully' => 'Votre commande a été passée avec succès',
        'order_has_been_canceled_successfully' => 'Commande annulée avec succès',
        'order_not_found' => 'Commande introuvable',
        'requested_for_cancellation' => 'Demande d\'annulation envoyée',
        'an_item_from_this_order_is_not_available_now' => 'Un article de cette commande n\'est plus disponible.',
        'remove_auction_product_from_cart_to_add_products' => 'Retirez le produit aux enchères pour ajouter des produits.',
        'remove_auction_product_from_cart_to_add_this_product' => 'Retirez le produit aux enchères pour ajouter ce produit.',
        'remove_other_products_from_cart_to_add_this_auction_product' => 'Retirez les autres produits pour ajouter ce produit aux enchères.',
        'this_auction_product_is_already_added_to_your_cart' => 'Ce produit aux enchères est déjà dans votre panier.',
        'you_can_not_re_order_an_auction_product' => 'Vous ne pouvez pas re-commander un produit aux enchères.',
        'delivery_status_changed_to_' => 'Statut de livraison changé en ',
        'delivery_status_has_been_changed_successfully' => 'Statut de livraison mis à jour avec succès',

        // Messages API - Paiement
        'payment_completed_successfully' => 'Paiement effectué avec succès.',
        'payment_cancelled' => 'Paiement annulé',
        'payment_failed' => 'Paiement échoué',
        'payment_failed_' => 'Paiement échoué !',
        'payment_failed_please_try_again' => 'Paiement échoué. Veuillez réessayer.',
        'payment_processing' => 'Paiement en cours',
        'payment_status_has_been_changed_successfully' => 'Statut de paiement mis à jour avec succès',
        'could_not_generate_payment_link' => 'Impossible de générer le lien de paiement',
        'payment_reference_id_or_challenge_is_missing' => 'Référence de paiement ou challenge manquant',
        'sensitive_data_or_signature_is_empty' => 'Données sensibles ou signature vides',
        'sensitive_data_or_signature_is_missing' => 'Données sensibles ou signature manquantes',
        'redirect_url_is_found' => 'URL de redirection trouvée',
        'the_transaction_has_been_initiated_successfully_you_can_complete_the_order_and_leave_this_page' => 'La transaction a été initiée avec succès. Vous pouvez finaliser la commande et quitter cette page.',
        'offline_recharge_has_been_done_please_wait_for_response' => 'Recharge hors ligne effectuée. Veuillez patienter.',
        'offline_payment_has_been_done_please_wait_for_response' => 'Paiement hors ligne effectué. Veuillez patienter.',
        'phonepev1_is_deprecated_please_use_phonepev2' => 'PhonePe V1 est obsolète, veuillez utiliser PhonePe V2',
        'insufficient_wallet_balance' => 'Solde du portefeuille insuffisant',
        'invalid_amount' => 'Montant invalide',
        'invalid_input' => 'Saisie invalide',
        'invalid_coupon_code' => 'Code de coupon invalide !',

        // Messages API - Coupons
        'coupon_applied' => 'Coupon appliqué',
        'coupon_removed' => 'Coupon retiré',
        'coupon_code_applied_successfully' => 'Code coupon appliqué avec succès',
        'coupon_expired' => 'Coupon expiré !',
        'coupon_has_been_deleted_successfully' => 'Coupon supprimé avec succès',
        'coupon_has_been_saved_successfully' => 'Coupon enregistré avec succès',
        'coupon_has_been_updated_successfully' => 'Coupon mis à jour avec succès',
        'the_coupon_is_already_applied_please_try_another_coupon' => 'Ce coupon est déjà appliqué. Essayez un autre.',
        'the_coupon_is_invalid' => 'Ce coupon est invalide',
        'this_coupon_is_not_applicable_to_your_cart_products' => 'Ce coupon n\'est pas applicable aux produits de votre panier !',
        'you_already_used_this_coupon' => 'Vous avez déjà utilisé ce coupon !',

        // Messages API - Wishlist
        'product_added_to_wishlist' => 'Produit ajouté à la liste de souhaits',
        'product_is_removed_from_wishlist' => 'Produit retiré de la liste de souhaits',
        'product_in_not_in_wishlist' => 'Produit absent de la liste de souhaits',
        'product_is_not_present_in_wishlist' => 'Produit absent de la liste de souhaits',
        'product_present_in_wishlist' => 'Produit présent dans la liste de souhaits',
        'you_need_to_login_as_a_customer_to_follow_this_seller' => 'Vous devez être connecté en tant que client pour suivre ce vendeur',

        // Messages API - Produits & Avis
        'review_submitted' => 'Avis soumis',
        'product_has_been_inserted_successfully' => 'Produit ajouté avec succès',
        'product_has_been_updated_successfully' => 'Produit mis à jour avec succès',
        'product_has_been_updated_successfully2' => 'Produit mis à jour avec succès.',
        'product_has_updated_successfully' => 'Produit mis à jour avec succès',
        'product_has_been_deleted_successfully' => 'Produit supprimé avec succès',
        'product_has_been_added_successfully' => 'Produit ajouté avec succès.',
        'product_delete_failed' => 'Échec de la suppression du produit',
        'product_delete_successfully' => 'Produit supprimé avec succès',
        'product_has_been_duplicated_successfully' => 'Produit dupliqué avec succès',
        'product_has_been_featured_successfully' => 'Produit mis en vedette avec succès',
        'product_has_been_unfeatured_successfully' => 'Produit retiré de la vedette avec succès',
        'product_has_been_published_successfully' => 'Produit publié avec succès',
        'product_has_been_unpublished_successfully' => 'Produit dépublié avec succès',
        'this_product_is_not_yours' => 'Ce produit ne vous appartient pas',
        'this_product_is_not_yours2' => 'Ce produit ne vous appartient pas.',
        'you_cannot_review_this_product' => 'Vous ne pouvez pas noter ce produit',
        'added_to_cart' => 'ajouté au panier.',

        // Messages API - Enchères
        'bid_placed_successfully' => 'Enchère placée avec succès.',
        'bid_deleted_successfully' => 'Enchère supprimée avec succès',
        'auction_bid' => 'Enchère',
        'auction_product_has_been_deleted_successfully' => 'Produit aux enchères supprimé avec succès',
        'auction_product_has_been_inserted_successfully' => 'Produit aux enchères ajouté avec succès',
        'auction_product_has_been_updated_successfully' => 'Produit aux enchères mis à jour avec succès',

        // Messages API - Vendeurs & Boutiques
        'seller_follow_is_successfull' => 'Vendeur suivi avec succès',
        'seller_unfollow_is_successfull' => 'Vendeur non suivi avec succès',
        'this_seller_is_followed' => 'Ce vendeur est suivi',
        'this_seller_is_unfollowed' => 'Ce vendeur n\'est plus suivi',
        'request_has_been_sent_successfully' => 'Demande envoyée avec succès',
        'request_sent' => 'Demande envoyée',

        // Messages API - Messagerie
        'message_send_successfully' => 'Message envoyé avec succès',
        'replied_successfully' => 'Réponse envoyée avec succès',
        'conversation_has_been_deleted_successfully' => 'Conversation supprimée avec succès',
        'conversation_is_disabled_at_this_moment' => 'La messagerie est désactivée pour le moment',
        'you_cannot_reply_to_this_query' => 'Vous ne pouvez pas répondre à cette demande',
        'you_cannot_send_this_message' => 'Vous ne pouvez pas envoyer ce message.',
        'you_cannot_see_this_message' => 'Vous ne pouvez pas voir ce message.',
        'this_query_is_not_yours' => 'Cette demande ne vous appartient pas',
        'sender' => 'Expéditeur',
        'hi_you_recieved_a_message_from_' => 'Bonjour ! Vous avez reçu un message de ',

        // Messages API - Fichiers & Uploads
        'file_has_been_inserted_successfully' => 'Fichier ajouté avec succès',
        'file_deleted_successfully' => 'Fichier supprimé avec succès',
        'file_deleted_failed' => 'Échec de la suppression du fichier',
        'file_does_not_exist' => 'Le fichier n\'existe pas !',
        'file_entry_deleted_from_database_file_was_not_found' => 'Entrée supprimée de la base (fichier introuvable)',
        'digital_product_deleted_successfully' => 'Produit numérique supprimé avec succès',
        'digital_product_has_been_inserted_successfully' => 'Produit numérique ajouté avec succès',
        'digital_product_has_been_updated_successfully' => 'Produit numérique mis à jour avec succès',
        'upload_limit_has_been_reached_please_upgrade_your_package' => 'Limite d\'envoi atteinte. Veuillez améliorer votre forfait.',
        'your_classified_product_upload_limit_has_been_reached_please_update_your_package' => 'Limite de produits classifiés atteinte. Veuillez mettre à jour votre forfait.',
        'you_have_more_uploaded_products_than_this_package_limit_you_need_to_remove_excessive_products_to_downgrade' => 'Vous avez plus de produits que la limite de ce forfait. Supprimez des produits pour passer à une offre inférieure.',

        // Messages API - Packages & Portefeuille
        'package_is_not_available' => 'Ce forfait n\'est pas disponible',
        'package_purchasing_successful' => 'Forfait acheté avec succès',
        'please_upgrade_your_package' => 'Veuillez mettre à niveau votre forfait',
        'please_upgrade_your_package2' => 'Veuillez mettre à niveau votre forfait.',
        'you_cannot_purchase_this_package_anymore' => 'Vous ne pouvez plus acheter ce forfait.',
        'successfully_converted' => 'Converti avec succès',
        'you_do_not_have_enough_balance_to_send_withdraw_request' => 'Solde insuffisant pour envoyer une demande de retrait',
        'pos_configuration_updated_successfully' => 'Configuration POS mise à jour avec succès',

        // Messages API - Notifications
        'notification_deleted_successfully' => 'Notification supprimée avec succès',

        // Messages API - Remboursements
        'refund_status_change_failed' => 'Échec du changement de statut de remboursement !',
        'refund_status_has_been_change_successfully' => 'Statut de remboursement changé avec succès',
        'submitted_successfully' => 'Soumis avec succès',

        // Messages API - Erreurs génériques
        'something_went_wrong' => 'Quelque chose s\'est mal passé',
        'something_went_wrong2' => 'Quelque chose s\'est mal passé !',
        'something_went_wrong3' => 'Quelque chose s\'est mal passé.',
        'something_went_wrong4' => 'Quelque chose s\'est mal passé !',
        'the_service_is_temporarily_unavailable_please_try_later' => 'Le service est temporairement indisponible. Réessayez plus tard.',

        // Module retrait livreur (clés normalisées = strtolower + espaces→_ + suppression des non-alphanum)
        'delivery_boy_withdrawal_requests' => 'Demandes de retrait des livreurs',
        'withdrawal_requests' => 'Demandes de retrait',
        'withdrawal_request' => 'Demande de retrait',
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'paid' => 'Payé',
        'rejected' => 'Rejeté',
        'all' => 'Tout',
        'delivery_boy' => 'Livreur',
        'amount' => 'Montant',
        'payment_method' => 'Mode de paiement',
        'account' => 'Compte',
        'date' => 'Date',
        'status' => 'Statut',
        'actions' => 'Actions',
        'view_details' => 'Voir le détail',
        'approve' => 'Approuver',
        'reject' => 'Rejeter',
        'confirm_payment' => 'Confirmer le paiement',
        'no_withdrawal_requests_found' => 'Aucune demande de retrait.',
        'approve_withdrawal' => 'Approuver le retrait ?',
        'approve_withdrawal_of' => 'Approuver le retrait de',
        'for' => 'pour',
        'mark_as_paid' => 'Marquer comme payé',
        'confirm_that_you_have_paid' => 'Confirmez que vous avez payé',
        'to' => 'à',
        'reject_withdrawal_request' => 'Rejeter la demande de retrait',
        'reason_for_rejection' => 'Motif du rejet',
        'explain_why_this_request_is_rejected' => 'Expliquez pourquoi cette demande est rejetée...',
        'withdrawal_request_details' => 'Détail de la demande de retrait',
        'requested_amount' => 'Montant demandé',
        'account_number' => 'Numéro de compte',
        'request_date' => 'Date de demande',
        'message' => 'Message',
        'current_earning_balance' => 'Solde de gains actuel',
        'insufficient_balance' => 'Solde insuffisant',
        'rejection_reason' => 'Motif du rejet',
        'payment_note' => 'Note de paiement',
        'admin_note' => 'Note admin',
        'optional' => 'optionnel',
        'eg_transferred_via_orange_money_txn123' => 'ex. Transféré via Orange Money #TXN123',
        'withdrawal_request_approved' => 'Demande de retrait approuvée.',
        'payment_confirmed_successfully' => 'Paiement confirmé avec succès.',
        'withdrawal_request_rejected' => 'Demande de retrait rejetée.',
        'this_request_is_no_longer_pending' => 'Cette demande n\'est plus en attente.',
        'only_approved_requests_can_be_confirmed_as_paid' => 'Seules les demandes approuvées peuvent être confirmées.',
        'insufficient_balance_for_this_withdrawal' => 'Solde insuffisant pour ce retrait.',
        'insufficient_balance_the_earning_may_have_changed' => 'Solde insuffisant. Les gains ont peut-être changé.',
        'delivery_boy_not_found' => 'Livreur introuvable.',
        'this_request_cannot_be_rejected' => 'Cette demande ne peut pas être rejetée.',
        'search_by_name_or_phone' => 'Rechercher par nom ou téléphone',
        'pending_count' => 'en attente',
        'minimum_withdrawal_amount' => 'Montant minimum de retrait',
        'minimum_amount_a_delivery_boy_can_request_to_withdraw' => 'Montant minimum qu\'un livreur peut demander à retirer.',
        'cash' => 'Espèces',
        'close' => 'Fermer',
        'cancel' => 'Annuler',
        'payment_successful' => 'Paiement réussi',
        'continue' => 'Continuer',
        'pickup_location_for_delivery_boy' => 'Emplacement de collecte pour le livreur',
        'monthly_earnings' => 'Gains mensuel',
    ];
}

function translate($key, $lang = null, $addslashes = false)
{
    if ($lang == null) {
        $lang = App::getLocale();
    }

    $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($key)));
    $lang_key = preg_replace('/_+/', '_', trim($lang_key, '_')); // normaliser (sauts de ligne/espaces → un seul _)

    // Pour le français : priorité au fallback (pour corriger les valeurs anglaises déjà en BDD)
    if ($lang === 'fr') {
        $fr_fallback = get_french_translations_fallback();
        if (isset($fr_fallback[$lang_key])) {
            $value = trim($fr_fallback[$lang_key]);
            Translation::updateOrCreate(
                ['lang' => 'fr', 'lang_key' => $lang_key],
                ['lang_value' => $value]
            );
            Cache::forget('translations-fr');
            return $addslashes ? addslashes($value) : $value;
        }
    }

    $translations_en = Cache::rememberForever('translations-en', function () {
        $arr = [];
        foreach (Translation::where('lang', 'en')->get() as $row) {
            $k = preg_replace('/_+/', '_', trim(preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($row->lang_key))), '_'));
            $arr[$k] = $row->lang_value;
        }
        return $arr;
    });

    if (!isset($translations_en[$lang_key])) {
        $translation_def = new Translation;
        $translation_def->lang = 'en';
        $translation_def->lang_key = $lang_key;
        $translation_def->lang_value = str_replace(array("\r", "\n", "\r\n"), "", $key);
        $translation_def->save();

        if (env('DEMO_MODE') != 'On') {
                $app_translation = new AppTranslation();
                $app_translation->lang = 'en';
                $app_translation->lang_key = $lang_key . '_ucf';
                $app_translation->lang_value = str_replace(array("\r", "\n", "\r\n"), "", $key);
                $app_translation->save();
            }

        Cache::forget('translations-en');
    }

    // return user session lang (clés normalisées pour correspondre aux blades avec sauts de ligne)
    $translation_locale = Cache::rememberForever("translations-{$lang}", function () use ($lang) {
        $arr = [];
        foreach (Translation::where('lang', $lang)->get() as $row) {
            $k = preg_replace('/_+/', '_', trim(preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($row->lang_key))), '_'));
            $arr[$k] = $row->lang_value;
        }
        return $arr;
    });
    if (isset($translation_locale[$lang_key])) {
        return $addslashes ? addslashes(trim($translation_locale[$lang_key])) : trim($translation_locale[$lang_key]);
    }

    // return default lang if session lang not found
    $translations_default = Cache::rememberForever('translations-' . env('DEFAULT_LANGUAGE', 'en'), function () {
        $arr = [];
        foreach (Translation::where('lang', env('DEFAULT_LANGUAGE', 'en'))->get() as $row) {
            $k = preg_replace('/_+/', '_', trim(preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($row->lang_key))), '_'));
            $arr[$k] = $row->lang_value;
        }
        return $arr;
    });
    if (isset($translations_default[$lang_key])) {
        return $addslashes ? addslashes(trim($translations_default[$lang_key])) : trim($translations_default[$lang_key]);
    }

    // fallback to en lang
    if (!isset($translations_en[$lang_key])) {
        return trim($key);
    }
    return $addslashes ? addslashes(trim($translations_en[$lang_key])) : trim($translations_en[$lang_key]);
}

function remove_invalid_charcaters($str)
{
    $str = str_ireplace(array("\\"), '', $str);
    return str_ireplace(array('"'), '\"', $str);
}

if (!function_exists('translation_tables')) {
    function translation_tables($uniqueIdentifier)
    {
        $noTableAddons =  ['african_pg', 'paytm', 'pos_system'];
        if (!in_array($uniqueIdentifier, $noTableAddons)) {
            $addons = [];
            $addons['affiliate'] = ['affiliate_options', 'affiliate_configs', 'affiliate_users', 'affiliate_payments', 'affiliate_withdraw_requests', 'affiliate_logs', 'affiliate_stats'];
            $addons['auction'] = ['auction_product_bids'];
            $addons['club_point'] = ['club_points', 'club_point_details'];
            $addons['delivery_boy'] = ['delivery_boys', 'delivery_histories', 'delivery_boy_payments', 'delivery_boy_collections'];
            $addons['offline_payment'] = ['manual_payment_methods'];
            $addons['otp_system'] = ['otp_configurations', 'sms_templates'];
            $addons['refund_request'] = ['refund_requests'];
            $addons['seller_subscription'] = ['seller_packages', 'seller_package_translations', 'seller_package_payments'];
            $addons['wholesale'] = ['wholesale_prices'];

            foreach ($addons as $key => $addon_tables) {
                if ($key == $uniqueIdentifier) {
                    foreach ($addon_tables as $table) {
                        Schema::dropIfExists($table);
                    }
                }
            }
        }
    }
}

function getShippingCost($carts, $index, $shipping_info = '', $carrier = '')
{
    //Log::alert('area Info', ['shipping_info' => $shipping_info]);
    $shipping_type = get_setting('shipping_type');
    $admin_products = array();
    $seller_products = array();
    $admin_product_total_weight = 0;
    $admin_product_total_price = 0;
    $seller_product_total_weight = array();
    $seller_product_total_price = array();

    $cartItem = $carts[$index];
    $product = Product::find($cartItem['product_id']);

    if ($product->digital == 1) {
        return 0;
    }

    foreach ($carts as $key => $cart_item) {
        $item_product = Product::find($cart_item['product_id']);
        if ($item_product->added_by == 'admin') {
            array_push($admin_products, $cart_item['product_id']);

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $admin_product_total_weight += ($item_product->weight * $cart_item['quantity']);
                $admin_product_total_price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
            }
        } else {
            $product_ids = array();
            $weight = 0;
            $price = 0;
            if (isset($seller_products[$item_product->user_id])) {
                $product_ids = $seller_products[$item_product->user_id];

                // For carrier wise shipping
                if ($shipping_type == 'carrier_wise_shipping') {
                    $weight += $seller_product_total_weight[$item_product->user_id];
                    $price += $seller_product_total_price[$item_product->user_id];
                }
            }

            array_push($product_ids, $cart_item['product_id']);
            $seller_products[$item_product->user_id] = $product_ids;

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $weight += ($item_product->weight * $cart_item['quantity']);
                $seller_product_total_weight[$item_product->user_id] = $weight;

                $price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
                $seller_product_total_price[$item_product->user_id] = $price;
            }
        }
    }

    if ($shipping_type == 'flat_rate') {
        return get_setting('flat_rate_shipping_cost') / count($carts);
    } elseif ($shipping_type == 'seller_wise_shipping') {
        if ($product->added_by == 'admin') {
            return get_setting('shipping_cost_admin') / count($admin_products);
        } else {
            return Shop::where('user_id', $product->user_id)->first()->shipping_cost / count($seller_products[$product->user_id]);
        }
    } elseif ($shipping_type == 'area_wise_shipping') {
         if (isset($shipping_info['area_id']) && $shipping_info['area_id'] !== null && $shipping_info['area_id']!=0) {
            $area = Area::where('id', $shipping_info['area_id'])->first();
        } else {
            $area = City::where('id', $shipping_info['city_id'])->first();
        }


        if ($area != null) {
            if ($product->added_by == 'admin') {
                return $area->cost / count($admin_products);
            } else {
                return $area->cost / count($seller_products[$product->user_id]);
            }
        }
        return 0;
    } elseif ($shipping_type == 'carrier_wise_shipping') { // carrier wise shipping
        $user_zone = $shipping_info['country_id'] != 0 ? Country::where('id', $shipping_info['country_id'])->first()->zone_id : 0;

        if ($carrier == null || $user_zone == 0) {
            return 0;
        }

        $carrier = Carrier::find($carrier);
        if ($carrier->carrier_ranges->first()) {
            $carrier_billing_type   = $carrier->carrier_ranges->first()->billing_type;
            if ($product->added_by == 'admin') {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $admin_product_total_weight : $admin_product_total_price;
            } else {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $seller_product_total_weight[$product->user_id] : $seller_product_total_price[$product->user_id];
            }
        }

        foreach ($carrier->carrier_ranges as $carrier_range) {
            if ($itemsWeightOrPrice >= $carrier_range->delimiter1 && $itemsWeightOrPrice < $carrier_range->delimiter2) {
                $carrier_price = $carrier_range->carrier_range_prices->where('zone_id', $user_zone)->first()->price;
                return $product->added_by == 'admin' ? ($carrier_price / count($admin_products)) : ($carrier_price / count($seller_products[$product->user_id]));
            }
        }
        return 0;
    } else {
        if ($product->is_quantity_multiplied && ($shipping_type == 'product_wise_shipping')) {
            return  $product->shipping_cost * $cartItem['quantity'];
        }
        return $product->shipping_cost;
    }
}

//return carrier wise shipping cost against seller
if (!function_exists('carrier_base_price')) {
    function carrier_base_price($carts, $carrier_id, $owner_id, $shipping_info = '')
    {
        $shipping = 0;
        foreach ($carts as $key => $cartItem) {
            if ($cartItem->owner_id == $owner_id) {
                $shipping_cost = getShippingCost($carts, $key, $shipping_info, $carrier_id);
                $shipping += $shipping_cost;
            }
        }
        return $shipping;
    }
}

//return seller wise carrier list
if (!function_exists('seller_base_carrier_list')) {
    function seller_base_carrier_list($owner_id, $userId = null, $tempUserId= null, $shipping_info = null)
    {
        $carrier_list = array();
        $carts = ($userId != null) ? Cart::where('user_id', $userId)->active()->get() : Cart::where('temp_user_id', $tempUserId)->active()->get();
        if (count($carts) > 0) {
            $zone = $shipping_info['country_id'] ? Country::where('id', $shipping_info['country_id'])->first()->zone_id : null;
            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id', function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->active()->get();
        }
        return (new CarrierCollection($carrier_list))->extra($owner_id, $carts, $shipping_info);
    }
}

function timezones()
{
    return array(
        '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
        '(GMT-11:00) Midway Island' => 'Pacific/Midway',
        '(GMT-11:00) Samoa' => 'Pacific/Apia',
        '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
        '(GMT-09:00) Alaska' => 'America/Anchorage',
        '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
        '(GMT-08:00) Tijuana' => 'America/Tijuana',
        '(GMT-07:00) Arizona' => 'America/Phoenix',
        '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
        '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
        '(GMT-07:00) La Paz' => 'America/Chihuahua',
        '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
        '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
        '(GMT-06:00) Central America' => 'America/Managua',
        '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
        '(GMT-06:00) Mexico City' => 'America/Mexico_City',
        '(GMT-06:00) Monterrey' => 'America/Monterrey',
        '(GMT-06:00) Saskatchewan' => 'America/Regina',
        '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
        '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
        '(GMT-05:00) Bogota' => 'America/Bogota',
        '(GMT-05:00) Lima' => 'America/Lima',
        '(GMT-05:00) Quito' => 'America/Bogota',
        '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
        '(GMT-04:00) Caracas' => 'America/Caracas',
        '(GMT-04:00) La Paz' => 'America/La_Paz',
        '(GMT-04:00) Santiago' => 'America/Santiago',
        '(GMT-03:30) Newfoundland' => 'America/St_Johns',
        '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
        '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Greenland' => 'America/Godthab',
        '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
        '(GMT-01:00) Azores' => 'Atlantic/Azores',
        '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
        '(GMT) Casablanca' => 'Africa/Casablanca',
        '(GMT) Dublin' => 'Europe/London',
        '(GMT) Edinburgh' => 'Europe/London',
        '(GMT) Lisbon' => 'Europe/Lisbon',
        '(GMT) London' => 'Europe/London',
        '(GMT) UTC' => 'UTC',
        '(GMT) Monrovia' => 'Africa/Monrovia',
        '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
        '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
        '(GMT+01:00) Berlin' => 'Europe/Berlin',
        '(GMT+01:00) Bern' => 'Europe/Berlin',
        '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
        '(GMT+01:00) Brussels' => 'Europe/Brussels',
        '(GMT+01:00) Budapest' => 'Europe/Budapest',
        '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
        '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
        '(GMT+01:00) Madrid' => 'Europe/Madrid',
        '(GMT+01:00) Paris' => 'Europe/Paris',
        '(GMT+01:00) Prague' => 'Europe/Prague',
        '(GMT+01:00) Rome' => 'Europe/Rome',
        '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
        '(GMT+01:00) Skopje' => 'Europe/Skopje',
        '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
        '(GMT+01:00) Vienna' => 'Europe/Vienna',
        '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
        '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
        '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
        '(GMT+02:00) Athens' => 'Europe/Athens',
        '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
        '(GMT+02:00) Cairo' => 'Africa/Cairo',
        '(GMT+02:00) Harare' => 'Africa/Harare',
        '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
        '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
        '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(GMT+02:00) Kyev' => 'Europe/Kiev',
        '(GMT+02:00) Minsk' => 'Europe/Minsk',
        '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
        '(GMT+02:00) Riga' => 'Europe/Riga',
        '(GMT+02:00) Sofia' => 'Europe/Sofia',
        '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
        '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
        '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
        '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
        '(GMT+03:00) Moscow' => 'Europe/Moscow',
        '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
        '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
        '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
        '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
        '(GMT+03:30) Tehran' => 'Asia/Tehran',
        '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
        '(GMT+04:00) Baku' => 'Asia/Baku',
        '(GMT+04:00) Muscat' => 'Asia/Muscat',
        '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
        '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
        '(GMT+04:30) Kabul' => 'Asia/Kabul',
        '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
        '(GMT+05:00) Islamabad' => 'Asia/Karachi',
        '(GMT+05:00) Karachi' => 'Asia/Karachi',
        '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
        '(GMT+05:30) Chennai' => 'Asia/Kolkata',
        '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
        '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
        '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
        '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
        '(GMT+06:00) Almaty' => 'Asia/Almaty',
        '(GMT+06:00) Astana' => 'Asia/Dhaka',
        '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
        '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
        '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
        '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
        '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
        '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
        '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
        '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
        '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
        '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
        '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
        '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
        '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
        '(GMT+08:00) Perth' => 'Australia/Perth',
        '(GMT+08:00) Singapore' => 'Asia/Singapore',
        '(GMT+08:00) Taipei' => 'Asia/Taipei',
        '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
        '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
        '(GMT+09:00) Osaka' => 'Asia/Tokyo',
        '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
        '(GMT+09:00) Seoul' => 'Asia/Seoul',
        '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
        '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
        '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
        '(GMT+09:30) Darwin' => 'Australia/Darwin',
        '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
        '(GMT+10:00) Canberra' => 'Australia/Sydney',
        '(GMT+10:00) Guam' => 'Pacific/Guam',
        '(GMT+10:00) Hobart' => 'Australia/Hobart',
        '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
        '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
        '(GMT+10:00) Sydney' => 'Australia/Sydney',
        '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
        '(GMT+11:00) Magadan' => 'Asia/Magadan',
        '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
        '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
        '(GMT+12:00) Auckland' => 'Pacific/Auckland',
        '(GMT+12:00) Fiji' => 'Pacific/Fiji',
        '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
        '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
        '(GMT+12:00) Wellington' => 'Pacific/Auckland',
        '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
    );
}

if (!function_exists('app_timezone')) {
    function app_timezone()
    {
        return config('app.timezone');
    }
}

//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if (($asset = Upload::find($id)) != null) {
            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        }
        return static_asset('assets/img/placeholder.jpg');
    }
}

if (!function_exists('my_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function my_asset($path, $secure = null)
    {
        if (config('filesystems.default') != 'local') {
            return Storage::disk(config('filesystems.default'))->url($path);
        }

        return app('url')->asset('public/' . $path, $secure);
    }
}

if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        return app('url')->asset('public/' . $path, $secure);
    }
}


// if (!function_exists('isHttps')) {
//     function isHttps()
//     {
//         return !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']);
//     }
// }

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        // Utiliser APP_URL si disponible (plus fiable)
        if (env('APP_URL')) {
            return rtrim(env('APP_URL'), '/') . '/';
        }
        
        // Fallback sur $_SERVER si disponible
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) {
            $root = '//' . $_SERVER['HTTP_HOST'];
            $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            return $root;
        }
        
        // Dernier recours
        return 'http://localhost/ecommerce/';
    }
}


if (!function_exists('getFileBaseURL')) {
    function getFileBaseURL()
    {
        if (env('FILESYSTEM_DRIVER') != 'local') {
            return env(Str::upper(env('FILESYSTEM_DRIVER')) . '_URL') . '/';
        }

        return getBaseURL() . 'public/';
    }
}


if (!function_exists('isUnique')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function isUnique($email)
    {
        $user = \App\Models\User::where('email', $email)->first();

        if ($user == null) {
            return '1'; // $user = null means we did not get any match with the email provided by the user inside the database
        } else {
            return '0';
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        $settings = Cache::remember('business_settings', 86400, function () {
            return BusinessSetting::all();
        });

        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}

function hex2rgba($color, $opacity = false)
{
    return (new ColorCodeConverter())->convertHexToRgba($color, $opacity);
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isSeller')) {
    function isSeller()
    {
        if (Auth::check() && Auth::user()->user_type == 'seller') {
            return true;
        }
        return false;
    }
}

if (!function_exists('isCustomer')) {
    function isCustomer()
    {
        if (Auth::check() && Auth::user()->user_type == 'customer') {
            return true;
        }
        return false;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// duplicates m$ excel's ceiling function
if (!function_exists('ceiling')) {
    function ceiling($number, $significance = 1)
    {
        return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
    }
}

//for api
if (!function_exists('get_images_path')) {
    function get_images_path($given_ids, $with_trashed = false)
    {
        $paths = [];
        foreach (explode(',', $given_ids) as $id) {
            $paths[] = uploaded_asset($id);
        }

        return $paths;
    }
}

//for api
if (!function_exists('get_videos_path')) {
    function get_videos_path($short_video_ids )
    {
        $paths = [];
        foreach (explode(',', $short_video_ids) as $id) {
            $paths[] = uploaded_asset($id);
        }

        return $paths;
    }
}

// Orange Money payment (BF gateway - doc API Orange Money BF)
if (!function_exists('normalizeOrangeCustomerMsisdn')) {
    /**
     * Normalise le numéro client pour Orange Money BF : chiffres uniquement, 8 chiffres (sans indicatif 226).
     */
    function normalizeOrangeCustomerMsisdn($customerNumber)
    {
        $digits = preg_replace('/\D/', '', $customerNumber);
        if (strlen($digits) === 11 && substr($digits, 0, 3) === '226') {
            return substr($digits, 3, 8);
        }
        if (strlen($digits) > 8 && substr($digits, 0, 2) === '22') {
            return substr($digits, -8);
        }
        return strlen($digits) >= 8 ? substr($digits, -8) : $digits;
    }
}

if (!function_exists('sendOrangeMoneyPayment')) {
    /**
     * Envoie une requête XML-RPC PAYMENT REQUEST à Orange Money (doc BF).
     *
     * @param string $customerNumber   Numéro client (sera normalisé en 8 chiffres BF)
     * @param int    $amount            Montant
     * @param string $otp               Code OTP reçu par SMS
     * @param string $reference_number  Info supplémentaire partenaire (ex. id commande)
     * @param string $ext_txn_id        Référence transaction partenaire (obligatoire dans la doc)
     */
    function sendOrangeMoneyPayment($customerNumber, $amount, $otp, $reference_number = '', $ext_txn_id = '')
    {
        $customer_msisdn = normalizeOrangeCustomerMsisdn($customerNumber);
        $ext_txn_id = $ext_txn_id !== '' ? $ext_txn_id : ('DAKWARI-' . time() . '-' . uniqid());
        $reference_number = $reference_number !== '' ? $reference_number : $ext_txn_id;

        $merchant = env('ORANGE_MONEY_MERCHANT_NUMBER');
        $api_user = env('ORANGE_MONEY_MERCHANT_ID');
        $api_pass = env('ORANGE_MONEY_MERCHANT_PASSWORD');

        $params = '<?xml version="1.0" encoding="UTF-8"?>
<COMMAND>
    <TYPE>OMPREQ</TYPE>
    <customer_msisdn>' . htmlspecialchars($customer_msisdn, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</customer_msisdn>
    <merchant_msisdn>' . htmlspecialchars($merchant, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</merchant_msisdn>
    <api_username>' . htmlspecialchars($api_user, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</api_username>
    <api_password>' . htmlspecialchars($api_pass, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</api_password>
    <amount>' . (int) $amount . '</amount>
    <PROVIDER>101</PROVIDER>
    <PROVIDER2>101</PROVIDER2>
    <PAYID>12</PAYID>
    <PAYID2>12</PAYID2>
    <otp>' . htmlspecialchars($otp, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</otp>
    <reference_number>' . htmlspecialchars($reference_number, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</reference_number>
    <ext_txn_id>' . htmlspecialchars($ext_txn_id, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ext_txn_id>
</COMMAND>';
        $url = get_setting('orange_sandbox') == 1
            ? 'https://testom.orange.bf/payment'
            : 'https://apiom.orange.bf/payment';
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $params);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        $response = curl_exec($session);
        curl_close($session);
        $response = '<response>' . $response . '</response>';
        $xml = @simplexml_load_string($response);
        if ($xml === false) {
            $obj = new \stdClass();
            $obj->status = 'ERROR';
            $obj->message = 'Invalid response from Orange Money';
            return $obj;
        }
        return json_decode(json_encode($xml));
    }
}

// Coris Money payment (paiement internet)
if (!function_exists('checkCorisTransactionStatus')) {
    /**
     * Vérifie le statut d'une transaction Coris Money (Section 8 de la doc).
     *
     * Après avoir reçu une confirmation de paiement, on appelle cet endpoint
     * pour s'assurer que le paiement est VRAIMENT confirmé chez Coris.
     *
     * @param string $transactionId Le transactionId reçu de sendCorisInternetPayment() (ex: "1900021")
     * @return object              Réponse avec ->success (bool), ->code, ->message, ->raw
     */
    function checkCorisTransactionStatus($transactionId)
    {
        $clientId = env('CORIS_CLIENT_ID');
        $clientSecret = env('CORIS_CLIENT_SECRET');
        $baseUrl = get_setting('coris_sandbox') == 1
            ? (env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'))
            : (env('CORIS_BASE_URL_PROD') ?: env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'));

        $obj = new \stdClass();

        if (!$clientId || !$clientSecret || !$baseUrl) {
            $obj->success = false;
            $obj->code = '-1';
            $obj->message = translate('Coris Money configuration is incomplete.');
            return $obj;
        }

        // Construction du hashParam selon la doc Coris Section 8
        // Ordre : codeOperation + clientSecret
        $stringToHash = $transactionId . $clientSecret;
        $hashParam = hash('sha256', $stringToHash);

        $endpoint = rtrim($baseUrl, '/') . '/operations/transaction-status';

        // Query string avec codeOperation (transactionId)
        $queryParams = http_build_query([
            'codeOperation' => $transactionId,
        ]);

        $url = $endpoint . '?' . $queryParams;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'clientId: ' . $clientId,
            'hashParam: ' . $hashParam,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($response, false);

        if ($curlError) {
            $obj->success = false;
            $obj->code = '-1';
            $obj->message = translate('Unable to verify payment status with Coris Money.');
            $obj->raw = $response;
            return $obj;
        }

        if (!$decoded || !isset($decoded->code)) {
            $obj->success = false;
            $obj->code = '-1';
            $obj->message = translate('Invalid response from Coris Money status check.');
            $obj->raw = $response;
            return $obj;
        }

        // Code = "0" signifie que la transaction est confirmée
        $obj->success = (string) $decoded->code === '0';
        $obj->code = (string) $decoded->code;
        $obj->message = isset($decoded->message) ? $decoded->message : '';
        $obj->transactionId = $transactionId;
        $obj->raw = $decoded;

        return $obj;
    }
}

if (!function_exists('sendCorisOTP')) {
    /**
     * Étape 1 du Service paiement de bien (Section 5 de la doc Coris).
     * Envoie un code OTP au téléphone du client via l'API Coris.
     *
     * Endpoint : POST /send-code-otp?codePays=&telephone=
     * Hash     : sha256(codePays + telephone + clientSecret)
     *
     * @param string $phoneNumber Numéro de téléphone du client
     * @return object Réponse avec ->success (bool), ->code, ->message
     */
    function sendCorisOTP($phoneNumber)
    {
        $clientId     = env('CORIS_CLIENT_ID');
        $clientSecret = env('CORIS_CLIENT_SECRET');
        $countryCode  = env('CORIS_COUNTRY_CODE', '226');

        $baseUrl = get_setting('coris_sandbox') == 1
            ? (env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'))
            : (env('CORIS_BASE_URL_PROD') ?: env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'));

        $obj = new \stdClass();

        if (!$clientId || !$clientSecret || !$baseUrl) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Coris Money configuration is incomplete.');
            return $obj;
        }

        $phone = preg_replace('/\D/', '', $phoneNumber);

        // Hash : codePays + telephone + clientSecret
        $hashParam = hash('sha256', $countryCode . $phone . $clientSecret);

        $url = rtrim($baseUrl, '/') . '/send-code-otp?' . http_build_query([
            'codePays'  => $countryCode,
            'telephone' => $phone,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'clientId: '  . $clientId,
            'hashParam: ' . $hashParam,
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Unable to reach Coris Money service. Please try again later.');
            return $obj;
        }

        $decoded = json_decode($response, false);

        // L'endpoint /send-code-otp retourne "codeErr" et "errCode" (pas "code")
        if (!$decoded || (!isset($decoded->codeErr) && !isset($decoded->errCode))) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Invalid response from Coris Money.');
            $obj->raw     = $response;
            return $obj;
        }

        $errorCode    = isset($decoded->codeErr) ? (string) $decoded->codeErr : (string) $decoded->errCode;
        $obj->success = $errorCode === '0';
        $obj->code    = $errorCode;
        $obj->message = isset($decoded->msg) ? $decoded->msg : (isset($decoded->text) ? $decoded->text : '');
        $obj->raw     = $decoded;

        return $obj;
    }
}

if (!function_exists('sendCorisPaiementBien')) {
    /**
     * Étape 2 du Service paiement de bien (Section 5 de la doc Coris).
     * Effectue le paiement avec le code OTP reçu par le client.
     *
     * Endpoint : POST /operations/paiement-bien?codePays=&telephone=&codePv=&montant=&codeOTP=
     * Hash     : sha256(codePays + telephone + codePv + montant + codeOTP + clientSecret)
     *
     * @param string     $phoneNumber Numéro de téléphone du client
     * @param int|float  $amount      Montant de la commande
     * @param string     $codeOTP     Code OTP reçu par le client
     * @return object Réponse avec ->success (bool), ->code, ->message, ->transactionId, ->amount
     */
    function sendCorisPaiementBien($phoneNumber, $amount, $codeOTP)
    {
        $clientId     = env('CORIS_CLIENT_ID');
        $clientSecret = env('CORIS_CLIENT_SECRET');
        $codePv       = env('CORIS_CODE_PV');
        $countryCode  = env('CORIS_COUNTRY_CODE', '226');

        $baseUrl = get_setting('coris_sandbox') == 1
            ? (env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'))
            : (env('CORIS_BASE_URL_PROD') ?: env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api'));

        $obj = new \stdClass();

        if (!$clientId || !$clientSecret || !$codePv || !$baseUrl) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Coris Money configuration is incomplete. Please check Client ID, Secret, Code PV and Base URLs.');
            return $obj;
        }

        $phone   = preg_replace('/\D/', '', $phoneNumber);
        $montant = (int) round($amount);

        // Hash : codePays + telephone + codePv + montant + codeOTP + clientSecret
        $hashParam = hash('sha256', $countryCode . $phone . $codePv . $montant . $codeOTP . $clientSecret);

        $url = rtrim($baseUrl, '/') . '/operations/paiement-bien?' . http_build_query([
            'codePays'  => $countryCode,
            'telephone' => $phone,
            'codePv'    => $codePv,
            'montant'   => $montant,
            'codeOTP'   => $codeOTP,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'clientId: '  . $clientId,
            'hashParam: ' . $hashParam,
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Unable to reach Coris Money service. Please try again later.');
            $obj->raw     = $response;
            return $obj;
        }

        $decoded = json_decode($response, false);

        if (!$decoded || (!isset($decoded->code) && !isset($decoded->codeErr) && !isset($decoded->errCode))) {
            $obj->success = false;
            $obj->code    = '-1';
            $obj->message = translate('Invalid response from Coris Money.');
            $obj->raw     = $response;
            return $obj;
        }

        $errorCode          = isset($decoded->code)    ? (string) $decoded->code
                            : (isset($decoded->codeErr) ? (string) $decoded->codeErr
                            : (string) $decoded->errCode);
        $obj->success       = $errorCode === '0';
        $obj->code          = $errorCode;
        $obj->message       = isset($decoded->msg)     ? $decoded->msg
                            : (isset($decoded->message) ? $decoded->message : '');
        $obj->transactionId = isset($decoded->transactionId) ? $decoded->transactionId : null;
        $obj->amount        = isset($decoded->montant)       ? $decoded->montant       : $montant;
        $obj->raw           = $decoded;

        return $obj;
    }
}

if (!function_exists('translateOMErrors')) {
    /**
     * Traduit les codes d'erreur Orange Money selon la doc API BF (tableau des codes d'erreurs).
     */
    function translateOMErrors($result)
    {
        if (!isset($result->status)) {
            return translate('An error occurred, please try again later');
        }
        $status = (string) $result->status;
        $messages = [
            // Montant
            '08'       => 'Le montant de la transaction est incorrect',
            '00042'    => 'Le montant demandé n\'est pas un multiple de la valeur configurée',
            '00043'    => 'Le montant de la transaction n\'est pas configuré dans le système',
            '409'      => 'Le montant de la transaction est inférieur au minimum autorisé',
            '00409'    => 'Le montant de la transaction est inférieur au minimum autorisé',
            '410'      => 'Le montant de la transaction dépasse le maximum autorisé',
            '00410'    => 'Le montant de la transaction dépasse le maximum autorisé',
            '99992'    => 'Le montant de la transaction est inférieur au minimum autorisé',
            '99993'    => 'Le montant dépasse la limite maximale autorisée',
            '99046'    => 'Le montant est inférieur au minimum défini pour ce service',
            '99047'    => 'Le montant dépasse la limite minimale définie',
            '100004'   => 'Le montant dépasse le maximum autorisé pour l\'expéditeur',
            '0100004'  => 'Le montant saisi dépasse le maximum autorisé pour ce service',
            '0100005'  => 'Le montant saisi dépasse le maximum autorisé pour le bénéficiaire',
            // Accès et service
            '00075'    => 'Vous n\'êtes pas autorisé à accéder à ce service',
            '84'       => 'Les frais de service ne sont pas définis (problème de configuration)',
            '00084'    => 'Les frais de service ne sont pas définis (problème de configuration)',
            '99034'    => 'Les frais de service ne sont pas définis (problème de configuration)',
            '00186'    => 'L\'initiateur de la requête est introuvable',
            '99987'    => 'Le service est inaccessible, veuillez réessayer plus tard',
            '90001'    => 'Transaction non autorisée',
            '01009'    => 'Une erreur est survenue avec le marchand',
            '09988'    => 'Une erreur est survenue lors de la transaction avec le marchand',
            '11007'    => 'Une erreur est survenue lors de la transaction avec le marchand',
            '1032'     => 'Problème de connexion avec le service PreTUPS',
            '01032'    => 'Une erreur est survenue dans le service, veuillez réessayer',
            '01035'    => 'Une erreur est survenue dans le service, veuillez réessayer',
            '916'      => 'Une erreur est survenue lors de la transaction bancaire',
            // Compte client
            '02117'    => 'Le compte utilisateur est bloqué, veuillez contacter le service client',
            '99990'    => 'Solde insuffisant pour effectuer cette transaction',
            '99996'    => 'Le portefeuille est suspendu, la transaction est impossible',
            '00066'    => 'Le numéro est invalide ou non lié à un compte Orange Money',
            '990422'   => 'Le numéro est invalide ou non lié à un compte Orange Money',
            '99039'    => 'L\'auto-transfert entre comptes bancaires n\'est pas autorisé',
            '99040'    => 'L\'auto-transfert entre portefeuilles n\'est pas autorisé',
            '1703'     => 'Le numéro de compte pour ce service est rejeté',
            '9035'     => 'Le numéro de compte pour ce service est rejeté',
            '01056'    => 'Le remboursement a été rejeté',
            '5001'     => 'La règle de transfert n\'est pas définie, transfert non autorisé',
            // OTP
            'OTPINV'   => 'Le code OTP est invalide',
            '990413'   => 'Code OTP incorrect, veuillez vérifier et réessayer',
            '990416'   => 'Code OTP incorrect, veuillez vérifier et réessayer',
            '990417'   => 'Le code OTP n\'existe pas',
            '990418'   => 'Le code OTP a déjà été utilisé',
            // Limites expéditeur — nombre de transactions
            '60011'    => 'Vous avez atteint le nombre maximum de transactions pour aujourd\'hui',
            '60012'    => 'Vous avez atteint le nombre maximum de transactions pour cette semaine',
            '60013'    => 'Vous avez atteint le nombre maximum de transactions pour ce mois',
            '0100012'  => 'Vous avez atteint le nombre maximum de transactions pour aujourd\'hui',
            '0100014'  => 'Vous avez atteint le nombre maximum de transactions pour cette semaine',
            '0100016'  => 'Vous avez atteint le nombre maximum de transactions pour ce mois',
            '100016'   => 'Vous avez atteint le nombre maximum de transactions pour ce mois',
            // Limites expéditeur — montant
            '60014'    => 'Vous avez atteint le montant maximum de transactions pour aujourd\'hui',
            '60015'    => 'Vous avez atteint le montant maximum de transactions pour cette semaine',
            '60016'    => 'Vous avez atteint le montant maximum de transactions pour ce mois',
            '0100024'  => 'Vous avez atteint le montant maximum de transactions pour aujourd\'hui',
            '0100026'  => 'Vous avez atteint le montant maximum de transactions pour cette semaine',
            '100028'   => 'Vous avez atteint le montant maximum de transactions pour ce mois',
            // Limites expéditeur — solde
            '60019'    => 'Solde insuffisant. 5 tentatives échouées bloqueront votre portefeuille',
            '60041'    => 'Le montant demandé dépasse votre limite journalière',
            // Limites bénéficiaire — nombre de transactions
            '60021'    => 'Le bénéficiaire a atteint le nombre maximum de transactions pour aujourd\'hui',
            '60022'    => 'Le bénéficiaire a atteint le nombre maximum de transactions pour cette semaine',
            '60023'    => 'Le bénéficiaire a atteint le nombre maximum de transactions pour ce mois',
            '0100013'  => 'Le bénéficiaire a atteint le nombre maximum de transactions pour aujourd\'hui',
            '0100015'  => 'Le bénéficiaire a atteint le nombre maximum de transactions pour cette semaine',
            '0100017'  => 'Le bénéficiaire a atteint le nombre maximum de transactions pour ce mois',
            // Limites bénéficiaire — montant
            '60024'    => 'Le bénéficiaire a atteint le montant maximum reçu pour aujourd\'hui',
            '60025'    => 'Le bénéficiaire a atteint le montant maximum reçu pour cette semaine',
            '60026'    => 'Le bénéficiaire a atteint le montant maximum reçu pour ce mois',
            '0100025'  => 'Le bénéficiaire a atteint le montant maximum reçu pour aujourd\'hui',
            '0100027'  => 'Le bénéficiaire a atteint le montant maximum reçu pour cette semaine',
            '100029'   => 'Le bénéficiaire a atteint le montant maximum reçu pour ce mois',
            '0100029'  => 'Le bénéficiaire a atteint le montant maximum reçu pour ce mois',
            // Limites bénéficiaire — solde
            '60030'    => 'Le bénéficiaire a atteint son solde maximum et ne peut pas recevoir ce transfert',
            '0100048'  => 'Le bénéficiaire a atteint la limite maximale de son portefeuille',
            // Pseudo / surnom
            '440'      => 'Le pseudo est trop long',
            '450'      => 'Les caractères spéciaux ne sont pas autorisés dans le pseudo',
            '3073'     => 'Erreur lors de la modification du pseudo',
            '3078'     => 'Ce pseudo est déjà utilisé',
            // Divers
            '111333'   => 'Le nom du mois saisi est incorrect',
        ];
        if (isset($messages[$status])) {
            return translate($messages[$status]);
        }
        return isset($result->message) && (string) $result->message !== '' ? $result->message : translate('Payment failed');
    }
}

// Moov Money payment (Mobicash CASH TRANSFER API)
if (!function_exists('initMoovMoneyPayment')) {
    function initMoovMoneyPayment($amount, $customerNumber)
    {
        $username = env('MOOV_MONEY_MERCHANT_ID');
        $password = env('MOOV_MONEY_MERCHANT_PASSWORD');
        $token = base64_encode($username . ':' . $password);
        $time = time();

        // URL complète Mobicash, à configurer dans l'env (via formulaire admin)
        $url = rtrim(env('MOOV_MOBICASH_URL', 'https://hwmm.moov-money.bf:38443/api/gateway/3pp/transaction/process/'), '/');

        $data = [
            'request-id' => 'DAKWARI-' . $time,
            'destination' => '226' . preg_replace('/\D/', '', $customerNumber),
            'amount' => (string) ((int) $amount),
            'remarks' => 'DAKWARI ORDER',
            'message' => 'PAYMENT OF ' . (int) $amount . ' TO DAKWARI PLEASE CONFIRM WITH PIN',
            'extended-data' => (object) [],
        ];

        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $username . $jsonBody);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $token,
            'command-id: mror-transaction-ussd',
            'hash: ' . $hash,
            'Content-Type: application/json',
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

if (!function_exists('handleMoovMoneyPayment')) {
    function handleMoovMoneyPayment($transactionId)
    {
        $username = env('MOOV_MONEY_MERCHANT_ID');
        $password = env('MOOV_MONEY_MERCHANT_PASSWORD');
        $token = base64_encode($username . ':' . $password);

        $url = rtrim(env('MOOV_MOBICASH_URL', 'https://hwmm.moov-money.bf:38443/api/gateway/3pp/transaction/process/'), '/');

        $data = [
            'request-id' => $transactionId,
        ];

        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $username . $jsonBody);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $token,
            'command-id: process-check-transaction',
            'hash: ' . $hash,
            'Content-Type: application/json',
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

//for api
if (!function_exists('checkout_done')) {
    function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::find($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            // Order paid notification to Customer, Seller, & Admin
            EmailUtility::order_email($order, 'paid'); 
            
            try {
                NotificationUtility::sendOrderPlacedNotification($order);
                calculateCommissionAffilationClubPoint($order);
            } catch (\Exception $e) {
            }
        }
    }
}

// get user total ordered products
if (!function_exists('get_user_total_ordered_products')) {
    function get_user_total_ordered_products()
    {
        $orders_query = Order::query();
        $orders       = $orders_query->where('user_id', Auth::user()->id)->get();
        $total        = 0;
        foreach ($orders as $order) {
            $total += count($order->orderDetails);
        }
        return $total;
    }
}

//for api
if (!function_exists('order_re_payment_done')) {
    function order_re_payment_done($order_id, $payment_method, $payment_details)
    {
        $order = Order::findOrFail($order_id);
        $order->payment_status = 'paid';
        $order->payment_details = $payment_details;
        $order->payment_type = $payment_method;
        $order->save();
        calculateCommissionAffilationClubPoint($order);

        if($order->notified == 0){
            NotificationUtility::sendOrderPlacedNotification($order);
            $order->notified = 1;
            $order->save();
        }

    }
}

//for api - Order Re Payment Done
if (!function_exists('wallet_payment_done')) {
    function wallet_payment_done($user_id, $amount, $payment_method, $payment_details)
    {
        $user = \App\Models\User::find($user_id);
        $user->balance = $user->balance + $amount;
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $amount;
        $wallet->payment_method = $payment_method;
        $wallet->payment_details = $payment_details;
        $wallet->save();
    }
}

// if (!function_exists('purchase_payment_done')) {
//     function purchase_payment_done($user_id, $package_id)
//     {
//         $user = User::findOrFail($user_id);
//         $user->customer_package_id = $package_id;
//         $customer_package = CustomerPackage::findOrFail($package_id);
//         $user->remaining_uploads += $customer_package->product_upload;
//         $user->save();

//         return 'success';
//     }
// }

if (!function_exists('seller_purchase_payment_done')) {
    function seller_purchase_payment_done($user_id, $seller_package_id, $payment_method, $payment_details)
    {
        $seller = Shop::where('user_id', $user_id)->first();
        $seller->seller_package_id = $seller_package_id;
        $seller_package = SellerPackage::findOrFail($seller_package_id);
        $seller->product_upload_limit = $seller_package->product_upload_limit;
        $seller->package_invalid_at = date('Y-m-d', strtotime($seller->package_invalid_at . ' +' . $seller_package->duration . 'days'));
        $seller->save();

        $seller_package = new SellerPackagePayment();
        $seller_package->user_id = $user_id;
        $seller_package->seller_package_id = $seller_package_id;
        $seller_package->payment_method = $payment_method;
        $seller_package->payment_details = $payment_details;
        $seller_package->approval = 1;
        $seller_package->offline_payment = 2;
        $seller_package->save();
    }
}

if (!function_exists('customer_purchase_payment_done')) {
    function customer_purchase_payment_done($user_id, $customer_package_id, $payment_method, $payment_details)
    {
        $user = User::findOrFail($user_id);
        $user->customer_package_id = $customer_package_id;
        $customer_package = CustomerPackage::findOrFail($customer_package_id);
        $user->remaining_uploads += $customer_package->product_upload;
        $user->save();

        $customer_package_payment = new CustomerPackagePayment();
        $customer_package_payment->user_id = $user->id;
        $customer_package_payment->customer_package_id = $customer_package_id;
        $customer_package_payment->amount = $customer_package->amount;
        $customer_package_payment->payment_method = $payment_method;
        $customer_package_payment->payment_details = $payment_details;
        $customer_package_payment->save();
    }
}

if (!function_exists('product_restock')) {
    function product_restock($orderDetail)
    {
        $variant = $orderDetail->variation;
        if ($orderDetail->variation == null) {
            $variant = '';
        }

        $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
            ->where('variant', $variant)
            ->first();

        if ($product_stock != null && (!in_array($orderDetail->delivery_status, ['delivered', 'cancelled']))) {
            $product = $product_stock->product;
            $product->num_of_sale -= $orderDetail->quantity;
            $product->save();

            $product_stock->qty += $orderDetail->quantity;
            $product_stock->save();
        }
    }
}

//Commission Calculation
if (!function_exists('calculateCommissionAffilationClubPoint')) {
    function calculateCommissionAffilationClubPoint($order)
    {
        (new CommissionController)->calculateCommission($order);

        if (addon_is_activated('affiliate_system')) {
            (new AffiliateController)->processAffiliatePoints($order);
        }

        if (addon_is_activated('club_point')) {
            if ($order->user != null) {
                (new ClubPointController)->processClubPoints($order);
            }
        }

        $order->commission_calculated = 1;
        $order->save();
    }
}

// Addon Activation Check
if (!function_exists('addon_is_activated')) {
    function addon_is_activated($identifier, $default = null)
    {
        $addons = Cache::remember('addons', 86400, function () {
            return Addon::all();
        });

        $activation = $addons->where('unique_identifier', $identifier)->where('activated', 1)->first();
        return $activation == null ? false : true;
    }
}

// Addon Activation Check
if (!function_exists('seller_package_validity_check')) {
    function seller_package_validity_check($user_id = null)
    {
        $user = $user_id == null ? \App\Models\User::find(Auth::user()->id) : \App\Models\User::find($user_id);
        $shop = $user->shop;
        $package_validation = false;
        if (
            $shop->product_upload_limit > $shop->user->products()->count()
            && $shop->package_invalid_at != null
            && Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) >= 0
        ) {
            $package_validation = true;
        }

        return $package_validation;
        // Ture = Seller package is valid and seller has the product upload limit
        // False = Seller package is invalid or seller product upload limit exists.
    }
}

if (!function_exists('seller_package_validity_check_for_preorder_product')) {
    function seller_package_validity_check_for_preorder_product($user_id = null)
    {
        $user = $user_id == null ? \App\Models\User::find(auth()->user()->id) : \App\Models\User::find($user_id);
        $shop = $user->shop;
        $package_validation = false;
        if (
            $shop->preorder_product_upload_limit > $user->preorderProducts()->count()
            && $shop->package_invalid_at != null
            && Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) >= 0
        ) {
            $package_validation = true;
        }
        return $package_validation;
    }
}

// Get URL params
if (!function_exists('get_url_params')) {
    function get_url_params($url, $key)
    {
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);

        return $query_params[$key] ?? '';
    }
}

// get Admin
if (!function_exists('get_admin')) {
    function get_admin()
    {
        $admin_query = User::query();
        return $admin_query->where('user_type', 'admin')->first();
    }
}

// Get slider images
if (!function_exists('get_slider_images')) {
    function get_slider_images($ids)
    {
        $slider_query = Upload::query();
        $sliders = $slider_query->whereIn('id', $ids);
        foreach ($ids as $id) {
            $sliders->orderByRaw("id!=?", [$id]);
        }
        return $sliders->get();
    }
}

if (!function_exists('get_featured_flash_deal')) {
    function get_featured_flash_deal()
    {
        $flash_deal_query = FlashDeal::query();
        $featured_flash_deal = $flash_deal_query->isActiveAndFeatured()
            ->where('start_date', '<=', strtotime(date('Y-m-d H:i:s')))
            ->where('end_date', '>=', strtotime(date('Y-m-d H:i:s')))
            ->first();

        return $featured_flash_deal;
    }
}

if (!function_exists('get_flash_deal_products')) {
    function get_flash_deal_products($flash_deal_id)
    {
        $flash_deal_product_query = FlashDealProduct::query();
        $flash_deal_product_query->where('flash_deal_id', $flash_deal_id);
        $flash_deal_products = $flash_deal_product_query->with('product')->orderBy('id', 'desc')->limit(10)->get();

        return $flash_deal_products;
    }
}

if (!function_exists('get_active_flash_deals')) {
    function get_active_flash_deals()
    {
        $activated_flash_deal_query = FlashDeal::query();
        $activated_flash_deal_query = $activated_flash_deal_query->where("status", 1);

        return $activated_flash_deal_query->get();
    }
}

if (!function_exists('get_product_active_flash_deal_end_date')) {
    function get_product_active_flash_deal_end_date($product_id, $discount_end_date)
    {
        $now = strtotime(now());

        return FlashDeal::where('status', 1)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('end_date', $discount_end_date)
            ->whereHas('flash_deal_products', function ($q) use ($product_id) {
                $q->where('product_id', $product_id);
            })
            ->orderBy('end_date', 'asc')
            ->value('end_date');
    }
}



if (!function_exists('get_active_taxes')) {
    function get_active_taxes()
    {
        $activated_tax_query = Tax::query();
        $activated_tax_query = $activated_tax_query->where("tax_status", 1);

        return $activated_tax_query->get();
    }
}

if (!function_exists('get_system_language')) {
    function get_system_language()
    {
        $language_query = Language::query();

        $locale = 'en';
        if (Session::has('locale')) {
            $locale = Session::get('locale', Config::get('app.locale'));
        }

        $language_query->where('code',  $locale);

        return $language_query->first();
    }
}

if (!function_exists('get_all_active_language')) {
    function get_all_active_language()
    {
        $language_query = Language::query();
        $language_query->where('status', 1);
        // Exclure Arabic et Bangla du site web (par code et par nom)
        $language_query->whereNotIn('code', ['ar', 'bn', 'bd']);
        $language_query->where(function ($q) {
            $q->where('name', 'not like', '%Bangla%')
              ->where('name', 'not like', '%Arabic%');
        });

        return $language_query->get();
    }
}

// get Session langauge
if (!function_exists('get_session_language')) {
    function get_session_language()
    {
        $language_query = Language::query();
        return $language_query->where('code', Session::get('locale', Config::get('app.locale')))->first();
    }
}

if (!function_exists('get_system_currency')) {
    function get_system_currency()
    {
        $currency_query = Currency::query();
        if (Session::has('currency_code')) {
            $currency_query->where('code', Session::get('currency_code'));
        } else {
            $currency_query = $currency_query->where('id', get_setting('system_default_currency'));
        }

        return $currency_query->first();
    }
}

if (!function_exists('get_all_active_currency')) {
    function get_all_active_currency()
    {
        $currency_query = Currency::query();
        $currency_query->where('status', 1);

        return $currency_query->get();
    }
}

/**
 * Libellé d'affichage pour une devise dans le sélecteur.
 * FCFA : affiche "XOF (FCFA)". Sinon : "Nom (Symbole)" ou "Nom" si identique.
 */
if (!function_exists('currency_display_label')) {
    function currency_display_label($currency)
    {
        if (!$currency) {
            return '';
        }
        $code = strtoupper(trim($currency->code ?? ''));
        $symbol = trim($currency->symbol ?? '');
        $name = strtoupper(trim($currency->name ?? ''));
        if ($code === 'XOF' || $code === 'FCFA' || $symbol === 'FCFA' || $name === 'FCFA') {
            return 'XOF (FCFA)';
        }
        $name = trim($currency->name ?? '');
        if ($name !== $symbol) {
            return $name . ' (' . $currency->symbol . ')';
        }
        return $name ?: $currency->symbol;
    }
}

/**
 * Devises affichées dans le sélecteur frontend : uniquement Euro, Dollar US et la devise par défaut (ex. FCFA).
 * Les prix sont déjà convertis automatiquement via convert_price() après changement de devise (session).
 */
if (!function_exists('get_frontend_currencies')) {
    function get_frontend_currencies()
    {
        $default_id = (int) get_setting('system_default_currency');
        $default = Currency::where('id', $default_id)->where('status', 1)->first();
        $eur = Currency::where('code', 'EUR')->where('status', 1)->first();
        $usd = Currency::where('code', 'USD')->where('status', 1)->first();

        $list = collect();
        if ($default) {
            $list->push($default);
        }
        if ($eur && (!$default || $eur->id !== $default->id)) {
            $list->push($eur);
        }
        if ($usd && (!$default || $usd->id !== $default->id)) {
            $list->push($usd);
        }

        return $list;
    }
}

if (!function_exists('get_single_product')) {
    function get_single_product($product_id)
    {
        $product_query = Product::query()->with('thumbnail');
        return $product_query->find($product_id);
    }
}

// get multiple Products
if (!function_exists('get_multiple_products')) {
    function get_multiple_products($product_ids)
    {
        $products_query = Product::query();
        return $products_query->whereIn('id', $product_ids)->get();
    }
}

// get count of products
if (!function_exists('get_products_count')) {
    function get_products_count($user_id = null)
    {
        $products_query = Product::query();
        if ($user_id) {
            $products_query = $products_query->where('user_id', $user_id);
        }
        return $products_query->isApprovedPublished()->count();
    }
}

// get minimum unit price of products
if (!function_exists('get_product_min_unit_price')) {
    function get_product_min_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->min('unit_price');
    }
}

// get maximum unit price of products
if (!function_exists('get_product_max_unit_price')) {
    function get_product_max_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->max('unit_price');
    }
}

if (!function_exists('get_featured_products')) {
    function get_featured_products()
    {
        return Cache::remember('featured_products', 3600, function () {
            $product_query = Product::query();
            return filter_products($product_query->where('featured', '1'))->latest()->limit(12)->get();
        });
    }
}

if (!function_exists('get_best_selling_products')) {
    function get_best_selling_products($limit, $user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return filter_products($product_query->orderBy('num_of_sale', 'desc'))->limit($limit)->get();
    }
}


//get todays deal Products
if (!function_exists('get_todays_deal_products')) {
    function get_todays_deal_products($limit, $user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return filter_products($product_query->where('todays_deal', '1'))->orderBy('id', 'desc')->limit($limit)->get();
    }
}

//get All Products
if (!function_exists('get_all_products')) {
    function get_all_products()
    {
        $product_query = Product::query();
        return filter_products($product_query)->orderBy('id', 'desc')->get();
    }
}

// Get Seller Products
if (!function_exists('get_seller_products')) {
    function get_seller_products($user_id)
    {
        $product_query = Product::query();
        return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('created_at', 'desc')->limit(15)->get();
    }
}

// Get Seller Best Selling Products
if (!function_exists('get_shop_best_selling_products')) {
    function get_shop_best_selling_products($user_id)
    {
        $product_query = Product::query();
        return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('num_of_sale', 'desc')->paginate(24);
    }
}

// Get all auction Products
if (!function_exists('get_all_auction_products')) {
    function get_auction_products($limit = null, $paginate = null)
    {
        $product_query = Product::query();
        $products = $product_query->latest()->isApprovedPublished()->where('auction_product', 1);
        if (get_setting('seller_auction_product') == 0) {
            $products = $products->where('added_by', 'admin');
        }
        $products = $products->where('auction_start_date', '<=', strtotime("now"))->where('auction_end_date', '>=', strtotime("now"));

        if ($limit) {
            $products = $products->limit($limit);
        } elseif ($paginate) {
            return $products->paginate($paginate);
        }
        return $products->get();
    }
}

//Get similiar classified products
if (!function_exists('get_similiar_classified_products')) {
    function get_similiar_classified_products($category_id = '', $product_id = '', $limit = '')
    {
        $classified_product_query = CustomerProduct::query();
        if ($category_id) {
            $classified_product_query->where('category_id', $category_id);
        }
        if ($product_id) {
            $classified_product_query->where('id', '!=', $product_id);
        }
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

//Get home page classified products
if (!function_exists('get_home_page_classified_products')) {
    function get_home_page_classified_products($limit = '')
    {
        $classified_product_query = CustomerProduct::query()->with('user', 'thumbnail');
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

// Customers Last viewed Products
if (!function_exists('lastViewedProducts')) {
    function lastViewedProducts($product_id, $user_id)
    {
        $lastViewedProduct = LastViewedProduct::firstOrCreate([
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);
        $lastViewedProduct->touch();

        $lastViewedProductsCount = LastViewedProduct::where('user_id', $user_id)->count();
        if($lastViewedProductsCount > 12) {
            $deleteRow = $lastViewedProductsCount - 12;
            LastViewedProduct::where('user_id', $user_id)->take($deleteRow)->delete();
        }
    }
}

// get auth users last viewed Products
if (!function_exists('getLastViewedProducts')) {
    function getLastViewedProducts()
    {
        $verified_sellers = verified_sellers_id();

        $lastViewedProduct = LastViewedProduct::where('user_id', auth()->user()->id)->orderBy('updated_at','desc')
                                ->whereIn("product_id", function ($query) use ($verified_sellers) {
                                    $query->select('id')
                                        ->from('products')
                                        ->where('approved', '1')->where('published', 1)
                                        ->when(!addon_is_activated('wholesale') ,function ($q1){
                                            $q1->where('wholesale_product', 0);
                                        })
                                        ->when(!addon_is_activated('auction') ,function ($q2){
                                            $q2->where('auction_product', 0);
                                        })
                                        ->when(get_setting('vendor_system_activation') == 0 ,function ($q3){
                                            $q3->where('added_by', 'admin');
                                        })
                                        ->when(get_setting('vendor_system_activation') == 1 ,function ($q4) use ($verified_sellers){
                                            $q4->where(function ($p1) use ($verified_sellers) {
                                                $p1->where('added_by', 'admin')->orWhere(function ($p2) use ($verified_sellers) {
                                                    $p2->whereIn('user_id', $verified_sellers);
                                                });
                                            });
                                        });
                                })->get();

        return $lastViewedProduct;
    }
}

// Get frequently bought product
if (!function_exists('get_frequently_bought_products')) {
    function get_frequently_bought_products($product)
    {
        $productSelectionType = $product->frequently_bought_selection_type;
        $fqbProducts = [];
        if($productSelectionType == 'product'){
            $fqbProductIds = $product->frequently_bought_products()->where('category_id', null)->pluck('frequently_bought_product_id')->toArray();
            $fqbProducts = filter_products(Product::whereIn('id', $fqbProductIds))->get();
        }
        elseif($productSelectionType == 'category'){
            $fqb_product_category = $product->frequently_bought_products()->where('category_id','!=', null)->first();
            $fqbCategoryID = $fqb_product_category != null ? $fqb_product_category->category_id : null;
            if($fqbCategoryID != null){
                $category = Category::with('childrenCategories')->find($fqbCategoryID);

                $fqbProducts = $category->products()->where('id','!=',$product->id);
                $fqbProducts = $product->added_by == 'admin' ? $fqbProducts->where('added_by', 'admin') : $fqbProducts->where('user_id', $product->user_id);

                $fqbProducts = filter_products($fqbProducts)->orderByRaw('RAND()')->take(10)->get();
            }
        }
        return $fqbProducts;
    }
}

// Get all brands
if (!function_exists('get_all_brands')) {
    function get_all_brands()
    {
        $brand_query = Brand::query();
        return  $brand_query->get();
    }
}

// Get single brands
if (!function_exists('get_brands')) {
    function get_brands($brand_ids)
    {
        $brand_query = Brand::query();
        $brands = $brand_query->whereIn('id', $brand_ids)->get();
        return $brands;
    }
}

// Get single brands
if (!function_exists('get_single_brand')) {
    function get_single_brand($brand_id)
    {
        $brand_query = Brand::query();
        return $brand_query->find($brand_id);
    }
}

// Get Brands by products
if (!function_exists('get_brands_by_products')) {
    function get_brands_by_products($usrt_id)
    {
        $product_query = Product::query();
        $brand_ids =  $product_query->where('user_id', $usrt_id)->isApprovedPublished()->whereNotNull('brand_id')->pluck('brand_id')->toArray();

        $brand_query = Brand::query();
        return $brand_query->whereIn('id', $brand_ids)->get();
    }
}

// Get category
if (!function_exists('get_category')) {
    function get_category($category_ids)
    {
        $category_query = Category::query();
        $category_query->with('coverImage');

        $category_query->whereIn('id', $category_ids);

        $categories = $category_query->get();
        return $categories;
    }
}

// Get single category
if (!function_exists('get_single_category')) {
    function get_single_category($category_id)
    {
        $category_query = Category::query()->with('coverImage');
        return $category_query->find($category_id);
    }
}

// Get categories by level zero
if (!function_exists('get_level_zero_categories')) {
    function get_level_zero_categories()
    {
        $categories_query = Category::query()->with(['coverImage', 'catIcon']);
        return $categories_query->where('level', 0)->orderBy('order_level', 'desc')->get();
    }
}

// Get categories by products
if (!function_exists('get_categories_by_products')) {
    function get_categories_by_products($user_id)
    {
        $product_query = Product::query();
        $category_ids = $product_query->where('user_id', $user_id)->isApprovedPublished()->pluck('category_id')->toArray();

        $category_query = Category::query();
        return $category_query->whereIn('id', $category_ids)->get();
    }
}
// Get categories by products
if (!function_exists('get_categories_by_preorder_products')) {
    function get_categories_by_preorder_products($user_id)
    {
        $product_query = PreorderProduct::query();
        $category_ids = $product_query->where('user_id', $user_id)->where('is_published', 1)->pluck('category_id')->toArray();

        $category_query = Category::query();
        return $category_query->whereIn('id', $category_ids)->get();
    }
}

// Get single Color name
if (!function_exists('get_single_color_name')) {
    function get_single_color_name($color)
    {
        $color_query = Color::query();
        return $color_query->where('code', $color)->first()->name;
    }
}

// Get single Attribute
if (!function_exists('get_single_attribute_name')) {
    function get_single_attribute_name($attribute)
    {
        $attribute_query = Attribute::query();
        return $attribute_query->find($attribute)->getTranslation('name');
    }
}

// Get user cart
if (!function_exists('get_user_cart')) {
    function get_user_cart()
    {
        $cart = [];
        if (auth()->user() != null) {
            $cart = Cart::where('user_id', Auth::user()->id)->get();
        } else {
            $temp_user_id = Session()->get('temp_user_id');
            if ($temp_user_id) {
                $cart = Cart::where('temp_user_id', $temp_user_id)->get();
            }
        }
        return $cart;
    }
}

// Get user Wishlist
if (!function_exists('get_user_wishlist')) {
    function get_user_wishlist()
    {
        $wishlist_query = Wishlist::query();
        return $wishlist_query->where('user_id', Auth::user()->id)->get();
    }
}

//Get best seller
if (!function_exists('get_best_sellers')) {
    function get_best_sellers($limit = '')
    {
        return Cache::remember('best_selers', 86400, function () use ($limit) {
            return Shop::where('verification_status', 1)->orderBy('num_of_sale', 'desc')->take($limit)->get();
        });
    }
}

//Get users followed sellers
if (!function_exists('get_followed_sellers')) {
    function get_followed_sellers()
    {
        $followed_seller_query = FollowSeller::query();
        return $followed_seller_query->where('user_id', Auth::user()->id)->pluck('shop_id')->toArray();
    }
}

// Get Order Details
if (!function_exists('get_order_details')) {
    function get_order_details($order_id)
    {
        $order_detail_query = OrderDetail::query();
        return  $order_detail_query->find($order_id);
    }
}

// Get Order Details
if (!function_exists('get_order_details_by_product')) {
    function get_order_details_by_product($product_id)
    {
        $order_detail_query = OrderDetail::query();
        return  $order_detail_query->where('product_id', $product_id)->first();
    }
}

// Get Order Details by review
if (!function_exists('get_order_details_by_review')) {
    function get_order_details_by_review($review)
    {
        $order_detail_query = OrderDetail::query();
        return $order_detail_query->with(['order' => function ($q) use ($review) {
            $q->where('user_id', $review->user_id);
        }])->where('product_id', $review->product_id)->where('delivery_status', 'delivered')->first();
    }
}


// Get user total expenditure
if (!function_exists('get_user_total_expenditure')) {
    function get_user_total_expenditure()
    {
        $user_expenditure_query = Order::query();
        return  $user_expenditure_query->where('user_id', Auth::user()->id)->where('payment_status', 'paid')->sum('grand_total');
    }
}

// Get count by delivery viewed
if (!function_exists('get_count_by_delivery_viewed')) {
    function get_count_by_delivery_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('delivery_viewed', 0)->get()->count();
    }
}

// Get delivery boy info
if (!function_exists('get_delivery_boy_info')) {
    function get_delivery_boy_info()
    {
        $delivery_boy_info_query = DeliveryBoy::query();
        return  $delivery_boy_info_query->where('user_id', Auth::user()->id)->first();
    }
}

// Get count by completed delivery
if (!function_exists('get_delivery_boy_total_completed_delivery')) {
    function get_delivery_boy_total_completed_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'delivered')
            ->count();
    }
}

// Get count by pending delivery
if (!function_exists('get_delivery_boy_total_pending_delivery')) {
    function get_delivery_boy_total_pending_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', '!=', 'delivered')
            ->where('delivery_status', '!=', 'cancelled')
            ->where('cancel_request', '0')
            ->count();
    }
}

// Get count by cancelled delivery
if (!function_exists('get_delivery_boy_total_cancelled_delivery')) {
    function get_delivery_boy_total_cancelled_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'cancelled')
            ->count();
    }
}

// Get count by payment status viewed
if (!function_exists('get_order_info')) {
    function get_order_info($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_user_order_by_id')) {
    function get_user_order_by_id($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->where('user_id', Auth::user()->id)->first();
    }
}

// Get Auction Product Bid Info
if (!function_exists('get_auction_product_bid_info')) {
    function get_auction_product_bid_info($bid_id = null)
    {
        $product_bid_info_query = AuctionProductBid::query();
        return  $product_bid_info_query->where('id', $bid_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_count_by_payment_status_viewed')) {
    function get_count_by_payment_status_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('payment_status_viewed', 0)->get()->count();
    }
}

// Get Uploaded file
if (!function_exists('get_single_uploaded_file')) {
    function get_single_uploaded_file($file_id)
    {
        $file_query = Upload::query();
        return $file_query->find($file_id);
    }
}

// Get single customer package file
if (!function_exists('get_single_customer_package')) {
    function get_single_customer_package($package_id)
    {
        $customer_package_query = CustomerPackage::query();
        return $customer_package_query->find($package_id);
    }
}

// Get single Seller package file
if (!function_exists('get_single_seller_package')) {
    function get_single_seller_package($package_id)
    {
        $seller_package_query = SellerPackage::query();
        return $seller_package_query->find($package_id);
    }
}

// Get user last wallet recharge
if (!function_exists('get_user_last_wallet_recharge')) {
    function get_user_last_wallet_recharge()
    {
        $recharge_query = Wallet::query();
        return $recharge_query->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first();
    }
}

// Get user total Club point
if (!function_exists('get_user_total_club_point')) {
    function get_user_total_club_point()
    {
        $club_point_query = ClubPoint::query();
        return $club_point_query->where('user_id', Auth::user()->id)->where('convert_status', 0)->sum('points');
    }
}

// Get all manual payment methods
if (!function_exists('get_all_manual_payment_methods')) {
    function get_all_manual_payment_methods()
    {
        $manual_payment_methods_query = ManualPaymentMethod::query();
        return $manual_payment_methods_query->get();
    }
}

// Get all blog category
if (!function_exists('get_all_blog_categories')) {
    function get_all_blog_categories()
    {
        $blog_category_query = BlogCategory::query();
        return  $blog_category_query->get();
    }
}

// Get all Pickup Points
if (!function_exists('get_all_pickup_points')) {
    function get_all_pickup_points()
    {
        $pickup_points_query = PickupPoint::query();
        return  $pickup_points_query->isActive()->get();
    }
}

// get Shop by user id
if (!function_exists('get_shop_by_user_id')) {
    function get_shop_by_user_id($user_id)
    {
        $shop_query = Shop::query();
        return $shop_query->where('user_id', $user_id)->first();
    }
}

// get Coupons
if (!function_exists('get_coupons')) {
    function get_coupons($user_id = null, $paginate = null)
    {
        $coupon_query = Coupon::query();
        $coupon_query = $coupon_query->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')));
        if ($user_id) {
            $coupon_query = $coupon_query->where('user_id', $user_id);
        }
        if ($paginate) {
            return $coupon_query->paginate($paginate);
        }
        return $coupon_query->get();
    }
}

// get non-viewed Conversations
if (!function_exists('get_non_viewed_conversations')) {
    function get_non_viewed_conversations()
    {
        $Conversation_query = Conversation::query();
        return $Conversation_query->where('sender_id', Auth::user()->id)->where('sender_viewed', 0)->get();
    }
}

// get non-viewed Conversations
if (!function_exists('get_non_viewed_preorder_conversations')) {
    function get_non_viewed_preorder_conversations()
    {
        $userId = in_array(auth()->user()->user_type, ['admin', 'staff']) ?  get_admin()->id : auth()->id();

        $numberOfUnreadMsg = PreorderConversationMessage::where('receiver_viewed', 0)
        ->whereHas('preorderConversationThread', function ($query) use ($userId) {
            $query->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });
        })
        ->where('sender_id', '!=', $userId)
        ->count();

        return $numberOfUnreadMsg;
    }
}

// get affliate option status
if (!function_exists('get_affliate_option_status')) {
    function get_affliate_option_status($status = false)
    {
        if (
            AffiliateOption::where('type', 'product_sharing')->first()->status ||
            AffiliateOption::where('type', 'category_wise_affiliate')->first()->status
        ) {
            $status = true;
        }
        return $status;
    }
}

// get affliate option purchase status
if (!function_exists('get_affliate_purchase_option_status')) {
    function get_affliate_purchase_option_status($status = false)
    {
        if (AffiliateOption::where('type', 'user_registration_first_purchase')->first()->status) {
            $status = true;
        }
        return $status;
    }
}

// get affliate config
if (!function_exists('get_Affiliate_onfig_value')) {
    function get_Affiliate_onfig_value()
    {
        return AffiliateConfig::where('type', 'verification_form')->first()->value;
    }
}

// Welcome Coupon add for user
if (!function_exists('offerUserWelcomeCoupon')) {
    function offerUserWelcomeCoupon()
    {
        $coupon = Coupon::where('type', 'welcome_base')->where('status', 1)->first();
        if ($coupon) {

            $couponDetails = json_decode($coupon->details);

            $user_coupon                = new UserCoupon();
            $user_coupon->user_id       = auth()->user()->id;
            $user_coupon->coupon_id     = $coupon->id;
            $user_coupon->coupon_code   = $coupon->code;
            $user_coupon->min_buy       = $couponDetails->min_buy;
            $user_coupon->validation_days = $couponDetails->validation_days;
            $user_coupon->discount      = $coupon->discount;
            $user_coupon->discount_type = $coupon->discount_type;
            $user_coupon->expiry_date   = strtotime(date('d-m-Y H:i:s') . ' +' . $couponDetails->validation_days . 'days');
            $user_coupon->save();
        }
    }
}

// get User Welcome Coupon
if (!function_exists('ifUserHasWelcomeCouponAndNotUsed')) {
    function ifUserHasWelcomeCouponAndNotUsed()
    {
        $user = auth()->user();
        $userCoupon = $user->userCoupon;
        if($userCoupon){
            if($userCoupon->expiry_date >=strtotime(date('d-m-Y H:i:s'))){
                $couponUse = $userCoupon->coupon->couponUsages->where('user_id',$user->id)->first();
                if(!$couponUse){
                    return $userCoupon;
                }
            }
        }

        return false;
    }
}


// Get Thumbnail Image
if (!function_exists('get_image')) {
    function get_image($image)
    {
        $image_url = static_asset('assets/img/placeholder.jpg');
        if ($image != null) {
            $image_url = $image->external_link == null ? my_asset($image->file_name) : $image->external_link;
        }
        return $image_url;
    }
}

//Get 1st prodyct image
if (!function_exists('get_first_product_image')) {
     function get_first_product_image($photos = null, $thumbnail = null)
    {
        $image_url = static_asset('assets/img/placeholder.jpg');
        $photos = $photos != null ? explode(',', $photos) : [];
        $photos = array_diff($photos, [$thumbnail]);
        $firstPhotoId = reset($photos);
        $image = null;
        if (!empty($firstPhotoId)) {
            $image = Upload::find($firstPhotoId);
        }
        if ($image == null && $thumbnail != null) {
            $image = Upload::find($thumbnail);
        }
        if ($image instanceof \Illuminate\Database\Eloquent\Collection) {
            $image = $image->first();
        }
        if ($image != null) {
            $image_url = $image->external_link == null ? my_asset($image->file_name) : $image->external_link;
        }
        return $image_url;
    }
}

// Get POS user cart
if (!function_exists('get_pos_user_cart')) {
    function get_pos_user_cart($sessionUserID = null, $sessionTemUserId = null)
    {
        $cart               = [];
        $authUser           = auth()->user();
        $owner_id           = in_array($authUser->user_type, ['admin','staff']) ? get_admin()->id : $authUser->id;

        if ($sessionUserID == null) {
            $sessionUserID = Session::has('pos.user_id') ? Session::get('pos.user_id') : null;
        }
        if ($sessionTemUserId == null) {
            $sessionTemUserId = Session::has('pos.temp_user_id') ? Session::get('pos.temp_user_id') : null;
        }

        $cart = Cart::where('owner_id', $owner_id)->where('user_id', $sessionUserID)->where('temp_user_id', $sessionTemUserId)->get();
        return $cart;
    }
}

// Get POS user cart
if (!function_exists('get_single_cart')) {
    function get_single_cart($cartID = null)
    {
        return Cart::findOrFail($cartID);
    }
}

if (!function_exists('number_format_short')) {
    function number_format_short($n, $precision = 1)
    {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }

        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }

        return $n_format . $suffix;
    }
}

// Get notification type
if (!function_exists('get_notification_type')) {
    function get_notification_type($value, $columnNamre)
    {
        $notificationType = NotificationType::query();
        $notificationType = $columnNamre == 'id' ? $notificationType->where('id', $value) : $notificationType->where('type', $value);
        return $notificationType->first();
    }
}

// Get all activate payment methods (uniquement Orange, Moov, Stripe pour ce projet)
if (!function_exists('get_activate_payment_methods')) {
    function get_activate_payment_methods()
    {
        $payment_methods = PaymentMethod::where('active', 1)
                                        ->whereIn('name', ['orange', 'moov', 'coris', 'stripe'])
                                        ->Where(function($query){
                                            $query->whereNull('addon_identifier')
                                            ->orWhere(function($q){
                                                if(addon_is_activated('paytm')){
                                                    $q->where('addon_identifier', 'paytm');
                                                }
                                            })
                                            ->orWhere(function($q){
                                                if(addon_is_activated('african_pg')){
                                                    $q->where('addon_identifier', 'african_pg');
                                                }
                                            })
                                            ->orWhere(function($q){
                                                if(addon_is_activated('knet')){
                                                    $q->where('addon_identifier', 'knet');
                                                }
                                            })
                                            ->orWhere(function($q){
                                                if(addon_is_activated('cybersource')){
                                                    $q->where('addon_identifier', 'cybersource');
                                                }
                                            });
                                        });
        return $payment_methods->get();
    }
}
// notification
if (! function_exists('flash_message')) {
    function flash_message($message, $level = 'info')
    {
        $notifications = session('flash_notification', collect());

        // Check if the message already exists
        if (!$notifications->contains('message', $message)) {
            session()->flash('flash_notification', $notifications->push([
                'message' => $message,
                'level' => $level,
            ]));
        }
    }
}

// Get wishlists
if (!function_exists('get_wishlists')) {
    function get_wishlists()
    {
        $verified_sellers = verified_sellers_id();
        $wishlists = Wishlist::where('user_id', auth()->user()->id)
                    ->whereIn("product_id", function ($query) use ($verified_sellers) {
                        $query->select('id')
                            ->from('products')
                            ->where('approved', '1')->where('published', 1)
                            ->when(!addon_is_activated('wholesale') ,function ($q1){
                                $q1->where('wholesale_product', 0);
                            })
                            ->when(!addon_is_activated('auction') ,function ($q2){
                                $q2->where('auction_product', 0);
                            })
                            ->when(get_setting('vendor_system_activation') == 0 ,function ($q3){
                                $q3->where('added_by', 'admin');
                            })
                            ->when(get_setting('vendor_system_activation') == 1 ,function ($q4) use ($verified_sellers){
                                $q4->where(function ($p1) use ($verified_sellers) {
                                    $p1->where('added_by', 'admin')->orWhere(function ($p2) use ($verified_sellers) {
                                        $p2->whereIn('user_id', $verified_sellers);
                                    });
                                });
                            });
                    })
                    ->latest();
        return $wishlists;
    }
}

// email template data
if (!function_exists('get_email_template_data')) {
    function get_email_template_data($identifier, $colmn_name = null)
    {
        $value = EmailTemplate::where('identifier', $identifier)->first()->$colmn_name;
        return $value;
    }
}

// Delete Product Reviews
if (!function_exists('deleteProductReview')) {
    function deleteProductReview($product)
    {
        if($product->added_by == 'seller' ){
            $seller = $product->user->shop;
            foreach($product->reviews as $review){
                $seller = $seller->fresh();
                $seller->rating = (($seller->rating * $seller->num_of_reviews) - $product->rating) / max(1, $seller->num_of_reviews - 1);
                $seller->num_of_reviews -= 1;
                $seller->save();
            }
        }
        $product->reviews()->delete();
    }
}

if (!function_exists('timezones')) {
    function timezones()
    {
        return array(
            '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
            '(GMT-11:00) Midway Island' => 'Pacific/Midway',
            '(GMT-11:00) Samoa' => 'Pacific/Apia',
            '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
            '(GMT-09:00) Alaska' => 'America/Anchorage',
            '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
            '(GMT-08:00) Tijuana' => 'America/Tijuana',
            '(GMT-07:00) Arizona' => 'America/Phoenix',
            '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
            '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
            '(GMT-07:00) La Paz' => 'America/Chihuahua',
            '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
            '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
            '(GMT-06:00) Central America' => 'America/Managua',
            '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
            '(GMT-06:00) Mexico City' => 'America/Mexico_City',
            '(GMT-06:00) Monterrey' => 'America/Monterrey',
            '(GMT-06:00) Saskatchewan' => 'America/Regina',
            '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
            '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
            '(GMT-05:00) Bogota' => 'America/Bogota',
            '(GMT-05:00) Lima' => 'America/Lima',
            '(GMT-05:00) Quito' => 'America/Bogota',
            '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
            '(GMT-04:00) Caracas' => 'America/Caracas',
            '(GMT-04:00) La Paz' => 'America/La_Paz',
            '(GMT-04:00) Santiago' => 'America/Santiago',
            '(GMT-03:30) Newfoundland' => 'America/St_Johns',
            '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
            '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Greenland' => 'America/Godthab',
            '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
            '(GMT-01:00) Azores' => 'Atlantic/Azores',
            '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
            '(GMT) Casablanca' => 'Africa/Casablanca',
            '(GMT) Dublin' => 'Europe/London',
            '(GMT) Edinburgh' => 'Europe/London',
            '(GMT) Lisbon' => 'Europe/Lisbon',
            '(GMT) London' => 'Europe/London',
            '(GMT) UTC' => 'UTC',
            '(GMT) Monrovia' => 'Africa/Monrovia',
            '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
            '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
            '(GMT+01:00) Berlin' => 'Europe/Berlin',
            '(GMT+01:00) Bern' => 'Europe/Berlin',
            '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
            '(GMT+01:00) Brussels' => 'Europe/Brussels',
            '(GMT+01:00) Budapest' => 'Europe/Budapest',
            '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
            '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
            '(GMT+01:00) Madrid' => 'Europe/Madrid',
            '(GMT+01:00) Paris' => 'Europe/Paris',
            '(GMT+01:00) Prague' => 'Europe/Prague',
            '(GMT+01:00) Rome' => 'Europe/Rome',
            '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
            '(GMT+01:00) Skopje' => 'Europe/Skopje',
            '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
            '(GMT+01:00) Vienna' => 'Europe/Vienna',
            '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
            '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
            '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
            '(GMT+02:00) Athens' => 'Europe/Athens',
            '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
            '(GMT+02:00) Cairo' => 'Africa/Cairo',
            '(GMT+02:00) Harare' => 'Africa/Harare',
            '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
            '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
            '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
            '(GMT+02:00) Kyev' => 'Europe/Kiev',
            '(GMT+02:00) Minsk' => 'Europe/Minsk',
            '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
            '(GMT+02:00) Riga' => 'Europe/Riga',
            '(GMT+02:00) Sofia' => 'Europe/Sofia',
            '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
            '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
            '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
            '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
            '(GMT+03:00) Moscow' => 'Europe/Moscow',
            '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
            '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
            '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
            '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
            '(GMT+03:30) Tehran' => 'Asia/Tehran',
            '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
            '(GMT+04:00) Baku' => 'Asia/Baku',
            '(GMT+04:00) Muscat' => 'Asia/Muscat',
            '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
            '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
            '(GMT+04:30) Kabul' => 'Asia/Kabul',
            '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
            '(GMT+05:00) Islamabad' => 'Asia/Karachi',
            '(GMT+05:00) Karachi' => 'Asia/Karachi',
            '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
            '(GMT+05:30) Chennai' => 'Asia/Kolkata',
            '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
            '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
            '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
            '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
            '(GMT+06:00) Almaty' => 'Asia/Almaty',
            '(GMT+06:00) Astana' => 'Asia/Dhaka',
            '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
            '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
            '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
            '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
            '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
            '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
            '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
            '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
            '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
            '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
            '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
            '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
            '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
            '(GMT+08:00) Perth' => 'Australia/Perth',
            '(GMT+08:00) Singapore' => 'Asia/Singapore',
            '(GMT+08:00) Taipei' => 'Asia/Taipei',
            '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
            '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
            '(GMT+09:00) Osaka' => 'Asia/Tokyo',
            '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
            '(GMT+09:00) Seoul' => 'Asia/Seoul',
            '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
            '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
            '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
            '(GMT+09:30) Darwin' => 'Australia/Darwin',
            '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
            '(GMT+10:00) Canberra' => 'Australia/Sydney',
            '(GMT+10:00) Guam' => 'Pacific/Guam',
            '(GMT+10:00) Hobart' => 'Australia/Hobart',
            '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
            '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
            '(GMT+10:00) Sydney' => 'Australia/Sydney',
            '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
            '(GMT+11:00) Magadan' => 'Asia/Magadan',
            '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
            '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
            '(GMT+12:00) Auckland' => 'Pacific/Auckland',
            '(GMT+12:00) Fiji' => 'Pacific/Fiji',
            '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
            '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
            '(GMT+12:00) Wellington' => 'Pacific/Auckland',
            '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
        );
    }
}


function formatToArray($input) {
    // Remove extra quotes from the string
    $cleanedString = trim($input, '"');
    
    // Split the string by commas to get each element
    $values = explode(',', $cleanedString);
    
    // Filter out "NaN" and non-numeric values, convert to integers
    $result = array_filter($values, function($value) {
        return is_numeric($value);
    });

    // Convert numeric values to integers
    $result = array_map('intval', $result);
    
    return $result;
}


//preorder_product_availability_check
if (!function_exists('preorder_product_availability_check')) {
    function preorder_product_availability_check($product)
    {
        if($product->is_available){
            return true;
        }
        $publishDate = Carbon::parse($product->available_date); 
        if (Carbon::today()->greaterThanOrEqualTo($publishDate)) {
            return true;
        }
        return false;
    }
}


// preorder steps fill color
if (!function_exists('preorder_fill_color')) {
    function preorder_fill_color($current_order_status, $previous_order_status = 0)
    {
        $color = match (true) {
            $current_order_status === 2 => '#28a745', 
            $current_order_status === 3 => '#dc3545', 
            $current_order_status === 1 || $previous_order_status == 2 => '#FF6002', 
            $current_order_status === 0 => '#9d9da6', 
            default => '#000000', 
        };
        return $color;
    }
}

// preorder discount in percentage
if (!function_exists('preorder_discount_in_percentage')) {
    function preorder_discount_in_percentage($product)
    {
        $base = preorder_home_base_price($product, false);
        $reduced = preorder_home_discounted_base_price($product, false);
        $discount = $base - $reduced;
        $dp = ($discount * 100) / ($base > 0 ? $base : 1);
        return round($dp);
    }
}

// preorder home base price
if (!function_exists('preorder_home_base_price')) {
    function preorder_home_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;
        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}


//Shows preorder Base Price with discount
if (!function_exists('preorder_home_discounted_base_price')) {
    function preorder_home_discounted_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;


        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}


// preorder steps fill color
if (!function_exists('preorder_status_show')) {
    function preorder_status_show($order)
    {
        $order_status = $order->status;
        $status_name = '';
        switch ($order_status) {
            case 'refund_status':
                $status_name = translate('Refund Requested');
                break;
            case 'delivery_status':
                $status_name = translate('Delivered');
                break;
            case 'shipping_status':
                $status_name = translate('In Shipping');
                break;
            case 'final_order_status':
                $status_name = translate('Final Order Request');
                break;
            case 'prepayment_confirm_status':
                $status_name = translate('Prepayment Request');
                break;
            case 'request_preorder_status':
                $status_name = translate('Preorder Request');
                break;
            default:
            $status_name = '';
                break;
        }

        return $status_name;
    }
}
// is_review_given
if (!function_exists('is_review_given')) {
    function is_review_given($order)
    {

         $review = PreorderProductReview::where('user_id', auth()->id())->where('preorder_product_id', $order->preorder_product->id)->first();
         if($review){
            return '#28a745';
         }
         return '#9d9da6';
    }
}
// preorder_discount_price
if (!function_exists('preorder_discount_price')) {
    function preorder_discount_price($product)
    {
        if($product->discount_start_date != null && (strtotime(date('d-m-Y')) > $product->discount_start_date || strtotime(date('d-m-Y')) < $product->discount_end_date)){
            $discount = $product->discount;
            $discounted_price = $product->discount_type == 'flat' ? $product->unit_price - $discount : $product->unit_price - ((($product->unit_price * $discount) / 100)) ;
        }else{
            $discounted_price = $product->unit_price;
        }
         return $discounted_price;
    }
}

// preorder_payment_type
if (!function_exists('preorder_payment_type')) {
    function preorder_payment_type($order)
    {
        $payment_type = translate('Manual');
        if($order->final_order_status != 0){
            $payment_type = translate('Final Payment');
        }
        if($order->prepayment != null){
            $payment_type = translate('Prepayment');
        }

        return $payment_type;
    }
}

// preorder product 
if (!function_exists('filter_preorder_product')) {
    function filter_preorder_product($products)
    {
        if (get_setting('vendor_system_activation') == 1) {
            return $products->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('user_type', 'admin');
                })->orWhereHas('user.shop', function ($q) {
                    $q->where('verification_status', 1);
                });
            });
        } else {
            return $products;
        }

    }
}


function filter_single_preorder_product($product)
{
    if (get_setting('vendor_system_activation') == 1) {
        $user = $product->user;

        if ($user->user_type == 'seller') {
            // Return the product only if the seller's shop is verified
            return optional($user->shop)->verification_status == 1 ? $product : null;
        }
        // Return the product if the user is not a seller (e.g., admin)
        return $product;
    } 
    
    // If vendor system is not activated, return the product directly
    return $product;
}


if (!function_exists('get_element_type_by_id')) {
    function get_element_type_by_id($id)
    {
        $elementType = ElementType::find($id);
        return $elementType ? strtolower(str_replace(' ', '', $elementType->name)) : null;
    }
}

if (!function_exists('get_element_style_value')) {
    function get_element_style_value($element_type_id, $name)
    {
        $style = ElementStyle::where('element_type_id', $element_type_id)
            ->where('name', $name)
            ->first();
        return $style ? $style->value : null;
    }
}



function convertToEmbedUrl($url)
{
    if (preg_match('/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    if (preg_match('/v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    return $url;
}

function youtubeVideoId($url)
{
    if (preg_match('/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return  $matches[1];
    }

    if (preg_match('/v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return  $matches[1];
    }

    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return  $matches[1];
    }

    return $url;
}

if (!function_exists('get_all_sale_alert_products')) {
    function get_all_sale_alert_products() {
        return CustomSaleAlert::with('product')->get()->map(function($alert) {
            if (!$alert->product) return null; 

            return [
                'id' => $alert->product->id,
                'title' => $alert->product->getTranslation('name'),
                'image' => uploaded_asset($alert->product->thumbnail_img),
                'url'  => route('product',  $alert->product->slug),
            ];
        })->filter();
    }
}

//get products label
if (!function_exists('get_custom_labels')) {
    function get_custom_labels($labels) {
        $labels_array = [];
        if($labels){
            $labels = explode(',',$labels);
            foreach($labels as $label){
                $label_data = CustomLabel::where('id',$label)->first();
                if($label_data){
                    $labels_array[] = $label_data;
                }
            }
        }
        return $labels_array;
    }
}


// clone file
if (!function_exists('clone_file')) {
    function clone_file($id)
    {
        if ($id != null && is_numeric($id)) {
            $file = Upload::find($id);
            if ($file) {
                $new_file = $file->replicate();
                $new_file->file_original_name = 'copy_of_' . $file->file_original_name;
                $new_file->file_name = Str::random(20) . '_' . basename($file->file_name);
                $new_file->user_id = User::where('user_type', 'admin')->first()->id;
                $new_file->save();
                copy(public_path($file->file_name), public_path($new_file->file_name));
                return $new_file->id;
            }
        }
        return null;
    }
}


//clone images
if (!function_exists('clone_images')) {
    function clone_images($ids)
    {
        $new_image_ids = [];
        if ($ids != null) {
            $ids_array = explode(',', $ids);
            foreach ($ids_array as $id) {
                $new_id = clone_file($id);
                if ($new_id) {
                    $new_image_ids[] = $new_id;
                }
            }
        }
        return implode(',', $new_image_ids);
    }
}


//fetch  gst applicable by product rate by  id
if (!function_exists('gst_applicable_product_rate')) {
    function gst_applicable_product_rate($product_id)
    {
       $product = Product::find($product_id);
    //    if (addon_is_activated('gst_system')  && ($product->gst_rate > 0 || ($product->gst_rate == 0 && $product->hsn_code != ''))){
    //         return $product->gst_rate;
    //    }
    if (addon_is_activated('gst_system')){
        return $product->gst_rate;
    }
       return null;
    }
}


//fetch gst by price and rate 
if (!function_exists('get_gst_by_price_and_rate')) {
    function get_gst_by_price_and_rate($price, $gst_rate)
    {
       if (addon_is_activated('gst_system')  && $gst_rate > 0){
            $gst_amount = ($price * $gst_rate) / 100;
            //return round($gst_amount, 2);
            return $gst_amount;
       }
       return 0;
    }
}

//compare is seller state and shipping state same or not BY order
if (! function_exists('same_state_shipping')) {
    function same_state_shipping($order)
    {
        $seller_state = isset($order->shop) ? (json_decode($order->shop->business_info)->state ?? null) : null;
        if(!$seller_state){
            $business_info = json_decode(get_setting('business_info'), true);
            if ($business_info && isset($business_info['state'])) {
                $seller_state = $business_info['state'];
            }
            else {
                $seller_state = null;
            }

        }
        $shipping_address = json_decode($order->shipping_address);
        //compare seller state and shipping state same or not
        if($seller_state && $shipping_address && isset($shipping_address->state) && $seller_state == $shipping_address->state){
            return true;
        }
        return false;

    }
}

//get seller GStin BY order
if (! function_exists('get_seller_gstin')) {
    function get_seller_gstin($order)
    {
        $gstin = null;
        if (isset($order->shop)) {
            $business_info = json_decode($order->shop->business_info, true);
            if ($business_info && isset($business_info['gstin'])) {
                $gstin = $business_info['gstin'];
            }
        }
        if(!$gstin){
            $business_info = json_decode(get_setting('business_info'), true);
            if ($business_info && isset($business_info['gstin'])) {
                $gstin = $business_info['gstin'];
            }
        }
        return $gstin;
    }
}

//get business info
if (! function_exists('admin_business_info')) {
    function admin_business_info()
    {
        return json_decode(get_setting('business_info'), true) ?? [];
    }
}

if (! function_exists('preorder_same_state_shipping')) {
    function preorder_same_state_shipping($order)
    {
        $seller_state_name = null;

        // 1. Get seller state from shop business_info
        if (
            isset($order->user) &&
            isset($order->user->shop) &&
            !empty($order->user->shop->business_info)
        ) {
            $shop_business = json_decode($order->user->shop->business_info, true);
            $seller_state_name = $shop_business['state'] ?? null;
        }
        if (empty($seller_state_name) && $order->product_owner =='admin') {
            $admin_business = json_decode(get_setting('business_info'), true);
            $seller_state_name = $admin_business['state'] ?? null;
        }
        $address = Address::find($order->address_id);
        if (!$address) {
            return false;
        }
        $shipping_state_name = null;

        if (!empty($address->state_id) && $address->state) {
            $shipping_state_name = $address->state->name ?? null;
        }
        if (empty($seller_state_name) || empty($shipping_state_name)) {
            return false;
        }
        return strtolower(trim($seller_state_name)) === strtolower(trim($shipping_state_name));
    }
}


//get POS discounted gst 
if (!function_exists('pos_cart_product_gst')) {
    function pos_cart_product_gst($cart_product, $product, $discount, $shipping,  $formatted = true)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        // $product_stock = $product->stocks->where('variant', $str)->first();
        // $price = $product_stock->price;

        $price = 0;
        $product_stock = $product->stocks->where('variant', $str)->first();
        if ($product_stock) {
            $price = $product_stock->price * $cart_product['quantity'];
        }

        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $cart_product['quantity'])->where('max_qty', '>=', $cart_product['quantity'])->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price * $cart_product['quantity'];
            }
        }
        if ($product->auction_product) {
            $price= $cart_product['price'] * $cart_product['quantity'];
        }

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }
        // Subtract coupon discount
        $price-= $discount;
        //Subtract shipping_cost
        $price+=$shipping;



        //calculation of gst
        $gst = 0;
        $gst += ($price * $product->gst_rate) / 100;

        if ($formatted) {
            return format_price(convert_price($gst));
        } else {
            return $gst;
        }
    }
}

//compare is seller state and shipping state same or not BY order
if (!function_exists('same_state_shipping_pos')) {
    function same_state_shipping_pos($shipping_state)
    {
        if (empty($shipping_state) || !is_string($shipping_state)) {
            return false;
        }

        if(Auth::user()->user_type=='seller'){
            $auth_user= Auth::user();
           if (empty($auth_user->shop) || empty($auth_user->shop->business_info)) {
            dd("sdc");
                return false;
            }

            $shop_business = json_decode($auth_user->shop->business_info, true);

            if (!is_array($shop_business) || empty($shop_business['state']) || !is_string($shop_business['state'])) {
                return false;
            }
            $seller_state = $shop_business['state'];
        }else{

            $businessInfoRaw = get_setting('business_info');
            if (empty($businessInfoRaw)) {
                return false;
            }

            $business_info = json_decode($businessInfoRaw, true);
            if (
                !is_array($business_info) ||
                empty($business_info['state']) ||
                !is_string($business_info['state'])
            ) {
                return false;
            }

            $seller_state = $business_info['state'];
        }
        return strtolower(trim($seller_state)) === strtolower(trim($shipping_state));
    }
}

// Get Same Seller product
if (!function_exists('get_same_seller_products')) {
    function get_same_seller_products($user_id, $limit = 20)
    {
        $products = Product::where('user_id', $user_id)->isApprovedPublished()->take($limit)->get();
        return $products;
    }
}

//Get Related Products by Category
if (!function_exists('get_related_products_by_category')) {
    function get_related_products_by_category($category_id, $limit = 20)
    {
        $products = Product::isApprovedPublished()->whereHas('categories', function ($query) use ($category_id) {
                        $query->where('category_id', $category_id);
                    })
                    ->take($limit)
                    ->get();
        return $products;
    }
}