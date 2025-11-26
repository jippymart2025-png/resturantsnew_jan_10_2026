<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\WithdrawMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WithdrawMethodController extends Controller
{
    protected array $withdrawSettings = [
        'stripe' => ['setting' => 'stripeSettings', 'label' => 'Stripe', 'default_enabled' => false],
        'razorpay' => ['setting' => 'razorpaySettings', 'label' => 'RazorPay', 'default_enabled' => true, 'force_enabled' => true],
        'paypal' => ['setting' => 'paypalSettings', 'label' => 'PayPal', 'default_enabled' => false],
        'flutterwave' => ['setting' => 'flutterWave', 'label' => 'FlutterWave', 'default_enabled' => false],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = $this->currentUser();
        $configuredMethods = [];

        if (!empty($user->userBankDetails)) {
            $configuredMethods[] = [
                'key' => 'bank',
                'label' => 'Bank Transfer',
                'route' => route('withdraw-method.create', ['method' => 'bank']),
            ];
        }

        $withdraw = $this->currentWithdrawRecord($user);
        if ($withdraw) {
            foreach ($this->withdrawSettings as $key => $meta) {
                $enabled = $this->withdrawEnabled(
                    $meta['setting'],
                    $meta['default_enabled'] ?? false,
                    $meta['force_enabled'] ?? false
                );
                if (!$enabled) {
                    continue;
                }

                if (!empty($withdraw->{$key})) {
                    $configuredMethods[] = [
                        'key' => $key,
                        'label' => $meta['label'],
                        'route' => route('withdraw-method.create', ['method' => $key]),
                    ];
                }
            }
        }

        return view('withdraw_method.index', [
            'configuredMethods' => $configuredMethods,
            'addRoute' => route('withdraw-method.create'),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->currentUser();
        $methodKey = strtolower($request->get('method', ''));

        if ($methodKey === '') {
            $withdraw = $this->currentWithdrawRecord($user);
            $methods = [];

            $methods[] = [
                'key' => 'bank',
                'label' => 'Bank Transfer',
                'configured' => !empty($user->userBankDetails),
                'fields' => $this->bankFields(),
                'values' => $user->userBankDetails ?? [],
            ];

            foreach ($this->withdrawSettings as $key => $meta) {
                $enabled = $this->withdrawEnabled(
                    $meta['setting'],
                    $meta['default_enabled'] ?? false,
                    $meta['force_enabled'] ?? false
                );

                if (!$enabled) {
                    continue;
                }

                $definition = $this->methodMeta($key, true);
                if (!$definition) {
                    continue;
                }

                $methods[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'configured' => $withdraw && !empty($withdraw->{$key}),
                    'fields' => $definition['fields'],
                    'values' => $withdraw->{$key} ?? [],
                ];
            }

            return view('withdraw_method.create', [
                'mode' => 'list',
                'methods' => $methods,
            ]);
        }

        return redirect()->route('withdraw-method.create');
    }

    public function store(Request $request)
    {
        $user = $this->currentUser();
        $methodKey = strtolower($request->input('method', ''));

        $method = $this->methodMeta($methodKey);
        if (!$method) {
            abort(404);
        }

        if ($methodKey === 'bank') {
            $data = $request->validate([
                'holderName' => 'required|string|max:255',
                'bankName' => 'required|string|max:255',
                'branchName' => 'required|string|max:255',
                'accountNumber' => 'required|string|max:255',
                'otherDetails' => 'nullable|string|max:500',
            ]);

            $user->userBankDetails = $data;
            $user->save();

            return redirect()->route('withdraw-method')->with('success', 'Bank details updated.');
        }

        $rules = match ($methodKey) {
            'stripe', 'razorpay' => ['accountId' => 'required|string|max:255'],
            'paypal' => ['email' => 'required|email|max:255'],
            'flutterwave' => [
                'accountNumber' => 'required|string|max:255',
                'bankCode' => 'required|string|max:255',
            ],
            default => [],
        };

        $data = $request->validate($rules);

        $payload = match ($methodKey) {
            'stripe', 'razorpay' => [
                'name' => $method['label'],
                'accountId' => $data['accountId'],
            ],
            'paypal' => [
                'name' => $method['label'],
                'email' => $data['email'],
            ],
            'flutterwave' => [
                'name' => $method['label'],
                'accountNumber' => $data['accountNumber'],
                'bankCode' => $data['bankCode'],
            ],
            default => [],
        };

        $withdraw = $this->currentWithdrawRecord($user) ?? new WithdrawMethod([
            'id' => Str::uuid()->toString(),
            'userId' => $user->firebase_id,
        ]);

        $withdraw->{$methodKey} = $payload;
        $withdraw->save();

        return redirect()->route('withdraw-method')->with('success', "{$method['label']} details saved.");
    }

    protected function currentUser(): User
    {
        return Auth::user();
    }

    protected function currentWithdrawRecord(User $user): ?WithdrawMethod
    {
        return WithdrawMethod::where('userId', $user->firebase_id)->first();
    }

    protected function withdrawEnabled(string $documentName, bool $default = false, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $fields = Setting::getFields($documentName);

        if (empty($fields)) {
            return $default;
        }

        return (bool) ($fields['isWithdrawEnabled'] ?? $default);
    }

    protected function methodMeta(string $method, bool $bypassEnabled = false): ?array
    {
        if ($method === 'bank') {
            return [
                'key' => 'bank',
                'label' => 'Bank Transfer',
                'fields' => $this->bankFields(),
            ];
        }

        $meta = $this->withdrawSettings[$method] ?? null;
        if (!$meta || (!$bypassEnabled && !$this->withdrawEnabled($meta['setting']))) {
            return null;
        }

        $fields = match ($method) {
            'stripe', 'razorpay' => [
                [
                    'name' => 'accountId',
                    'label' => trans('lang.app_setting_accountId'),
                    'type' => 'text',
                    'help' => trans('lang.app_setting_razorpay_accountId_help'),
                    'placeholder' => 'acc_XXXXXXXXXXXX',
                ],
            ],
            'paypal' => [
                [
                    'name' => 'email',
                    'label' => trans('lang.app_setting_paypal_email'),
                    'type' => 'email',
                    'help' => trans('lang.app_setting_paypal_email_help'),
                ],
            ],
            'flutterwave' => [
                [
                    'name' => 'accountNumber',
                    'label' => trans('lang.app_setting_flutterwave_accountnumber'),
                    'type' => 'text',
                    'help' => trans('lang.app_setting_flutterwave_accountnumber_help'),
                ],
                [
                    'name' => 'bankCode',
                    'label' => trans('lang.app_setting_flutterwave_bankcode'),
                    'type' => 'text',
                    'help' => trans('lang.app_setting_flutterwave_bankcode_help'),
                ],
            ],
            default => [],
        };

        return [
            'key' => $method,
            'label' => $meta['label'],
            'fields' => $fields,
        ];
    }
    protected function bankFields(): array
    {
        return [
            [
                'name' => 'holderName',
                'label' => trans('lang.app_setting_bank_holder_name'),
                'type' => 'text',
                'help' => trans('lang.app_setting_bank_holder_name_help'),
            ],
            [
                'name' => 'bankName',
                'label' => trans('lang.app_setting_bank_name'),
                'type' => 'text',
                'help' => trans('lang.app_setting_bank_name_help'),
            ],
            [
                'name' => 'branchName',
                'label' => trans('lang.app_setting_bank_branch'),
                'type' => 'text',
                'help' => trans('lang.app_setting_bank_branch_help'),
            ],
            [
                'name' => 'accountNumber',
                'label' => trans('lang.app_setting_bank_account'),
                'type' => 'text',
                'help' => trans('lang.app_setting_bank_account_help'),
            ],
            [
                'name' => 'otherDetails',
                'label' => trans('lang.app_setting_bank_other'),
                'type' => 'textarea',
                'help' => trans('lang.app_setting_bank_other_help'),
            ],
        ];
    }
}
