import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type CoopLabelAuthShape = {
    roles?: string[];
    user?: {
        account_type?: string | null;
    };
};

const MULTI_COOP_ROLES = new Set(['super admin', 'provincial admin']);

const normalizeRole = (role: unknown) =>
    typeof role === 'string' ? role.trim().toLowerCase() : '';

export function useCoopLabel() {
    const page = usePage();

    const auth = computed(() => (page.props.auth || {}) as CoopLabelAuthShape);

    const normalizedRoles = computed(() => {
        const roles = Array.isArray(auth.value.roles) ? auth.value.roles : [];
        const accountTypeRole = auth.value.user?.account_type;

        return [
            ...roles.map(normalizeRole),
            normalizeRole(accountTypeRole),
        ].filter(Boolean);
    });

    const isMultiCoopRole = computed(() =>
        normalizedRoles.value.some((role) => MULTI_COOP_ROLES.has(role)),
    );

    const cooperativeLabel = computed(() =>
        isMultiCoopRole.value ? 'Cooperatives' : 'Cooperative',
    );

    const cooperativeLabelLower = computed(() => cooperativeLabel.value.toLowerCase());
    const cooperativeManagementLabel = computed(() => `${cooperativeLabel.value} Management`);
    const allCooperativesLabel = computed(() =>
        isMultiCoopRole.value ? 'All Cooperatives' : 'Cooperative',
    );
    const availableCooperativesLabel = computed(() =>
        `Available ${cooperativeLabel.value}`,
    );
    const noCooperativesFoundLabel = computed(() =>
        `No ${cooperativeLabel.value} found.`,
    );
    const totalCooperativesLabel = computed(() =>
        `total ${cooperativeLabelLower.value}`,
    );

    return {
        isMultiCoopRole,
        cooperativeLabel,
        cooperativeLabelLower,
        cooperativeManagementLabel,
        allCooperativesLabel,
        availableCooperativesLabel,
        noCooperativesFoundLabel,
        totalCooperativesLabel,
    };
}
