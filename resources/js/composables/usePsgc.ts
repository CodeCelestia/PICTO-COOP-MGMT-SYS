import type { Ref } from 'vue';
import { ref } from 'vue';

interface PsgcLocation {
    code: string;
    name: string;
    regionName?: string;
    provinceName?: string;
    cityMunicipalityName?: string;
}

interface PsgcRegion {
    code: string;
    name: string;
    regionName: string;
}

interface PsgcProvince {
    code: string;
    name: string;
    regionCode: string;
    regionName: string;
}

interface PsgcCity {
    code: string;
    name: string;
    provinceCode: string;
    provinceName: string;
    regionCode: string;
    regionName: string;
}

interface PsgcBarangay {
    code: string;
    name: string;
    cityCode: string;
    cityName: string;
    provinceCode: string;
    provinceName: string;
    regionCode: string;
    regionName: string;
}

let regionsCache: PsgcRegion[] | null = null;
let regionsRequest: Promise<PsgcRegion[]> | null = null;
const provincesCache = new Map<string, PsgcProvince[]>();
const provincesRequests = new Map<string, Promise<PsgcProvince[]>>();
const citiesCache = new Map<string, PsgcCity[]>();
const citiesRequests = new Map<string, Promise<PsgcCity[]>>();
const barangaysCache = new Map<string, PsgcBarangay[]>();
const barangaysRequests = new Map<string, Promise<PsgcBarangay[]>>();

export function usePsgc() {
    const regions: Ref<PsgcRegion[]> = ref([]);
    const provinces: Ref<PsgcProvince[]> = ref([]);
    const cities: Ref<PsgcCity[]> = ref([]);
    const barangays: Ref<PsgcBarangay[]> = ref([]);
    
    const loading = ref(false);
    const error: Ref<string | null> = ref(null);

    // PSGC API base URL
    const API_BASE = 'https://psgc.gitlab.io/api';

    const fetchRegions = async () => {
        error.value = null;

        if (regionsCache) {
            regions.value = [...regionsCache];
            return;
        }

        loading.value = true;
        try {
            if (!regionsRequest) {
                regionsRequest = fetch(`${API_BASE}/regions/`)
                    .then(async (response) => {
                        if (!response.ok) throw new Error('Failed to fetch regions');
                        const data = await response.json();
                        return data.map((item: any) => ({
                            code: item.code,
                            name: item.name,
                            regionName: item.regionName,
                        })) as PsgcRegion[];
                    })
                    .finally(() => {
                        regionsRequest = null;
                    });
            }

            const result = await regionsRequest;
            regionsCache = result;
            regions.value = [...result];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching regions:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchProvinces = async (regionCode: string) => {
        error.value = null;
        provinces.value = [];
        cities.value = [];
        barangays.value = [];

        if (!regionCode) {
            return;
        }

        const cached = provincesCache.get(regionCode);
        if (cached) {
            provinces.value = [...cached];
            return;
        }

        loading.value = true;
        
        try {
            if (!provincesRequests.has(regionCode)) {
                provincesRequests.set(
                    regionCode,
                    fetch(`${API_BASE}/regions/${regionCode}/provinces/`)
                        .then(async (response) => {
                            if (!response.ok) throw new Error('Failed to fetch provinces');
                            const data = await response.json();
                            return data.map((item: any) => ({
                                code: item.code,
                                name: item.name,
                                regionCode: item.regionCode,
                                regionName: item.regionName,
                            })) as PsgcProvince[];
                        })
                        .finally(() => {
                            provincesRequests.delete(regionCode);
                        }),
                );
            }

            const result = await provincesRequests.get(regionCode)!;
            provincesCache.set(regionCode, result);
            provinces.value = [...result];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching provinces:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchCities = async (provinceCode: string) => {
        error.value = null;
        cities.value = [];
        barangays.value = [];

        if (!provinceCode) {
            return;
        }

        const cached = citiesCache.get(provinceCode);
        if (cached) {
            cities.value = [...cached];
            return;
        }

        loading.value = true;
        
        try {
            if (!citiesRequests.has(provinceCode)) {
                citiesRequests.set(
                    provinceCode,
                    fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`)
                        .then(async (response) => {
                            if (!response.ok) throw new Error('Failed to fetch cities/municipalities');
                            const data = await response.json();
                            return data.map((item: any) => ({
                                code: item.code,
                                name: item.name,
                                provinceCode: item.provinceCode,
                                provinceName: item.provinceName,
                                regionCode: item.regionCode,
                                regionName: item.regionName,
                            })) as PsgcCity[];
                        })
                        .finally(() => {
                            citiesRequests.delete(provinceCode);
                        }),
                );
            }

            const result = await citiesRequests.get(provinceCode)!;
            citiesCache.set(provinceCode, result);
            cities.value = [...result];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching cities:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchBarangays = async (cityCode: string) => {
        error.value = null;
        barangays.value = [];

        if (!cityCode) {
            return;
        }

        const cached = barangaysCache.get(cityCode);
        if (cached) {
            barangays.value = [...cached];
            return;
        }

        loading.value = true;
        
        try {
            if (!barangaysRequests.has(cityCode)) {
                barangaysRequests.set(
                    cityCode,
                    fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`)
                        .then(async (response) => {
                            if (!response.ok) throw new Error('Failed to fetch barangays');
                            const data = await response.json();
                            return data.map((item: any) => ({
                                code: item.code,
                                name: item.name,
                                cityCode: item.cityCode || item.cityMunicipalityCode,
                                cityName: item.cityName || item.cityMunicipalityName,
                                provinceCode: item.provinceCode,
                                provinceName: item.provinceName,
                                regionCode: item.regionCode,
                                regionName: item.regionName,
                            })) as PsgcBarangay[];
                        })
                        .finally(() => {
                            barangaysRequests.delete(cityCode);
                        }),
                );
            }

            const result = await barangaysRequests.get(cityCode)!;
            barangaysCache.set(cityCode, result);
            barangays.value = [...result];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching barangays:', e);
        } finally {
            loading.value = false;
        }
    };

    return {
        regions,
        provinces,
        cities,
        barangays,
        loading,
        error,
        fetchRegions,
        fetchProvinces,
        fetchCities,
        fetchBarangays,
    };
}
