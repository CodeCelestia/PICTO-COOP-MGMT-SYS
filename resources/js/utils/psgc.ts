/**
 * Philippine Standard Geographic Code (PSGC) API Integration
 * API: https://psgc.gitlab.io/api/
 */

export interface Region {
    code: string;
    name: string;
}

export interface Province {
    code: string;
    name: string;
}

export interface CityMunicipality {
    code: string;
    name: string;
}

export interface Barangay {
    code: string;
    name: string;
}

/**
 * Fetch all regions from PSGC API
 */
export async function fetchRegions(): Promise<Region[]> {
    try {
        const response = await fetch('https://psgc.gitlab.io/api/regions/');
        if (!response.ok) throw new Error('Failed to fetch regions');
        return await response.json();
    } catch (error) {
        console.error('Error fetching regions:', error);
        return [];
    }
}

/**
 * Fetch provinces for a specific region
 */
export async function fetchProvinces(regionCode: string): Promise<Province[]> {
    try {
        const response = await fetch(
            `https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`
        );
        if (!response.ok) throw new Error('Failed to fetch provinces');
        return await response.json();
    } catch (error) {
        console.error('Error fetching provinces:', error);
        return [];
    }
}

/**
 * Fetch cities/municipalities for a specific province
 */
export async function fetchCitiesMunicipalities(
    provinceCode: string
): Promise<CityMunicipality[]> {
    try {
        const response = await fetch(
            `https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`
        );
        if (!response.ok) throw new Error('Failed to fetch cities/municipalities');
        return await response.json();
    } catch (error) {
        console.error('Error fetching cities/municipalities:', error);
        return [];
    }
}

/**
 * Fetch barangays for a specific city/municipality
 */
export async function fetchBarangays(
    cityMunicipalityCode: string
): Promise<Barangay[]> {
    try {
        const response = await fetch(
            `https://psgc.gitlab.io/api/cities-municipalities/${cityMunicipalityCode}/barangays/`
        );
        if (!response.ok) throw new Error('Failed to fetch barangays');
        return await response.json();
    } catch (error) {
        console.error('Error fetching barangays:', error);
        return [];
    }
}
