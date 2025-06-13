import axios from 'axios';

const API_BASE_URL = 'http://localhost/tcexam/admin/code/api.php';

const api = axios.create({
    baseURL: API_BASE_URL,
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
    },
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
    window.location.href = `${API_BASE_URL}?action=download_excel&test_id=${testId}&group_id=${groupId}`;
}; 