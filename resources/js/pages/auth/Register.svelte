<script module lang="ts">
    export const layout = {
        title: 'إنشاء حساب جديد',
        description: 'أدخل بياناتك لإنشاء حساب جديد',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { login } from '@/routes';
    import { store } from '@/routes/register';

    let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title="إنشاء حساب" />

<Form
    {...store.form()}
    resetOnSuccess={['password', 'password_confirmation']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="name" class="font-label text-xs">الاسم الكامل</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    name="name"
                    placeholder="الاسم الكامل"
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="font-label text-xs">البريد الإلكتروني</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="font-label text-xs">كلمة المرور</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="كلمة المرور"
                    passwordrules={passwordRules}
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="font-label text-xs">تأكيد كلمة المرور</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="تأكيد كلمة المرور"
                    passwordrules={passwordRules}
                    class="h-11 rounded-full border-border bg-background px-5"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-full bg-foreground font-label text-xs text-background hover:bg-foreground/90"
                disabled={processing}
                data-test="register-user-button"
            >
                {#if processing}<Spinner />{/if}
                إنشاء حساب
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            لديك حساب بالفعل؟
            <TextLink href={login()} class="font-label text-xs">
                تسجيل الدخول
            </TextLink>
        </div>
    {/snippet}
</Form>
