<div
    x-data="{
        status: 'checking',
        busy: false,
        async refresh() {
            this.status = window.UniHupPush ? await window.UniHupPush.state() : 'unsupported';
        },
        async toggle() {
            if (this.busy) return;
            this.busy = true;
            try {
                this.status = this.status === 'subscribed'
                    ? await window.UniHupPush.disable()
                    : await window.UniHupPush.enable();
            } catch (e) {
                this.status = String(e || 'error');
            } finally {
                this.busy = false;
            }
        },
    }"
    x-init="refresh()"
    class="text-sm"
>
    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            x-show="['subscribed', 'unsubscribed', 'denied', 'error'].includes(status)"
            x-on:click="toggle()"
            x-bind:disabled="busy || status === 'denied'"
            class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold ring-1 transition disabled:opacity-50"
            x-bind:class="status === 'subscribed'
                ? 'bg-white text-danger-600 ring-danger-300 hover:bg-danger-50 dark:bg-white/5 dark:ring-danger-500/40'
                : 'bg-primary-600 text-white ring-primary-600 hover:bg-primary-500'"
        >
            <span x-text="status === 'subscribed' ? 'Turn off on this device' : 'Turn on for this device'"></span>
        </button>

        <span class="text-xs text-gray-500 dark:text-gray-400">
            <template x-if="status === 'checking'"><span>Checking…</span></template>
            <template x-if="status === 'subscribed'"><span>On for this browser.</span></template>
            <template x-if="status === 'unsubscribed'"><span>Off for this browser.</span></template>
            <template x-if="status === 'denied'"><span>Blocked in your browser settings — allow notifications for this site, then reload.</span></template>
            <template x-if="status === 'unsupported'"><span>This browser doesn't support push notifications.</span></template>
            <template x-if="status === 'unconfigured'"><span>Push is not configured on the server yet.</span></template>
            <template x-if="status === 'error'"><span>Something went wrong — try reloading.</span></template>
        </span>
    </div>
</div>
