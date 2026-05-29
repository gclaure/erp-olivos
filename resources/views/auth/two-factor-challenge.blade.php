<x-auth-layout>
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            
            ="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $dispatch('clear-2fa-auth-code');

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : $dispatch('focus-2fa-auth-code');
                    });
                },
            }">
            <div>
                <x-auth-header
                    :title="__('Authentication Code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')" />
            </div>

            <div>
                <x-auth-header
                    :title="__('Recovery Code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')" />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div>
                        <div class="flex items-center justify-center my-5">
                            <flux:otp
                                
                                length="6"
                                name="code"
                                label="OTP Code"
                                label:sr-only
                                class="mx-auto" />
                        </div>
                    </div>

                    <div>
                        <div class="my-5">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                
                                
                                autocomplete="one-time-code" />
                        </div>

                        @error('recovery_code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full">
                        {{ __('Continue') }}
                    </flux:button>
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">{{ __('or you can') }}</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span>{{ __('login using a recovery code') }}</span>
                        <span>{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-auth-layout>
