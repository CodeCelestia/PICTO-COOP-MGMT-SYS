import { ref, Ref } from 'vue';

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
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`${API_BASE}/regions/`);
            if (!response.ok) throw new Error('Failed to fetch regions');
            const data = await response.json();
            regions.value = data.map((item: any) => ({
                code: item.code,
                name: item.name,
                regionName: item.regionName,
            }));
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching regions:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchProvinces = async (regionCode: string) => {
        loading.value = true;
        error.value = null;
        provinces.value = [];
        cities.value = [];
        barangays.value = [];
        
        try {
            const response = await fetch(`${API_BASE}/regions/${regionCode}/provinces/`);
            if (!response.ok) throw new Error('Failed to fetch provinces');
            const data = await response.json();
            provinces.value = data.map((item: any) => ({
                code: item.code,
                name: item.name,
                regionCode: item.regionCode,
                regionName: item.regionName,
            }));
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching provinces:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchCities = async (provinceCode: string) => {
        loading.value = true;
        error.value = null;
        cities.value = [];
        barangays.value = [];
        
        try {
            const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
            if (!response.ok) throw new Error('Failed to fetch cities/municipalities');
            const data = await response.json();
            cities.value = data.map((item: any) => ({
                code: item.code,
                name: item.name,
                provinceCode: item.provinceCode,
                provinceName: item.provinceName,
                regionCode: item.regionCode,
                regionName: item.regionName,
            }));
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('Error fetching cities:', e);
        } finally {
            loading.value = false;
        }
    };

    const fetchBarangays = async (cityCode: string) => {
        loading.value = true;
        error.value = null;
        barangays.value = [];
        
        try {
            const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
            if (!response.ok) throw new Error('Failed to fetch barangays');
            const data = await response.json();
            barangays.value = data.map((item: any) => ({
                code: item.code,
                name: item.name,
                cityCode: item.cityCode || item.cityMunicipalityCode,
                cityName: item.cityName || item.cityMunicipalityName,
                provinceCode: item.provinceCode,
                provinceName: item.provinceName,
                regionCode: item.regionCode,
                regionName: item.regionName,
            }));
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
