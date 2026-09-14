<script module lang="ts">
    export const layout = {
        title: 'تسجيل الدخول',
        description: 'أدخل بريدك الإلكتروني وكلمة المرور لتسجيل الدخول',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';

    let {
        status = '',
        canResetPassword,
    }: {
        status?: string;
        canResetPassword: boolean;
    } = $props();
</script>

<AppHead title="تسجيل الدخول" />

{#if status}
    <div class="mb-4 rounded-lg bg-mint/20 p-3 text-center text-sm font-medium text-foreground">
        {status}
    </div>
{/if}

<PasskeyVerify />

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email" class="font-label text-xs">البريد الإلكتروني</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="font-label text-xs">كلمة المرور</Label>
                    {#if canResetPassword}
                        <TextLink href={request()} class="font-label text-xs">
                            نسيت كلمة المرور؟
                        </TextLink>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="كلمة المرور"
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="remember" name="remember" />
                <Label for="remember" class="text-sm font-normal">
                    تذكرني
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-full bg-foreground font-label text-xs text-background hover:bg-foreground/90"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                تسجيل الدخول
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            ليس لديك حساب؟
            <TextLink href={register()} class="font-label text-xs">إنشاء حساب</TextLink>
        </div>
    {/snippet}
</Form>
