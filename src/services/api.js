import axios from 'axios';

const API_BASE_URL = 'https://macom.manubanyuputih.id/admin/code/api.php';
const API_KEY = 'hahay'; // GANTI DENGAN KUNCI RAHASIA YANG SAMA DENGAN BACKEND!

const api = axios.create({
    baseURL: API_BASE_URL,
    // withCredentials: true, // Tidak diperlukan lagi dengan API Key
    headers: {
        'Content-Type': 'application/json',
    },
});

// Add API key to all requests
api.interceptors.request.use(config => {
    config.params = {
        ...config.params,
        api_key: API_KEY
    };
    return config;
}, error => {
    return Promise.reject(error);
});

export const getModules = async () => {
    try {
        const response = await api.get('?action=get_modules');
        return response.data;
    } catch (error) {
        console.error('Error fetching modules:', error);
        throw error;
    }
};

export const getGroups = async (moduleId) => {
    try {
        const response = await api.get(`?action=get_groups&module_id=${moduleId}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching groups:', error);
        throw error;
    }
};

export const getTests = async (groupId) => {
    try {
        const response = await api.get(`?action=get_tests&group_id=${groupId}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching tests:', error);
        throw error;
    }
};

export const downloadExcel = (testId, groupId) => {
    window.location.href = `${API_BASE_URL}?action=download_excel&test_id=${testId}&group_id=${groupId}&api_key=${API_KEY}`;
};

export const downloadModuleZip = (moduleId) => {
    window.location.href = `${API_BASE_URL}?action=download_module_zip&module_id=${moduleId}&api_key=${API_KEY}`;
};

export const downloadGroupZip = (groupId) => {
    window.location.href = `${API_BASE_URL}?action=download_group_zip&group_id=${groupId}&api_key=${API_KEY}`;
}; 