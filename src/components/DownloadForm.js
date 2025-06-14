import React, { useState, useEffect } from 'react';
import {
    Box,
    Container,
    Paper,
    Typography,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    Button,
    CircularProgress,
    Alert,
    Snackbar
} from '@mui/material';
import { getModules, getGroups, getTests, downloadExcel, downloadModuleZip, downloadGroupZip } from '../services/api';

function DownloadForm() {
    // State for data
    const [modules, setModules] = useState([]);
    const [groups, setGroups] = useState([]);
    const [tests, setTests] = useState([]);

    // State for selections
    const [selectedModule, setSelectedModule] = useState('');
    const [selectedGroup, setSelectedGroup] = useState('');
    const [selectedTest, setSelectedTest] = useState('');

    // State for loading and error
    const [loading, setLoading] = useState({
        modules: false,
        groups: false,
        tests: false
    });
    const [error, setError] = useState(null);

    // Fetch modules on component mount
    useEffect(() => {
        const fetchModules = async () => {
            setLoading(prev => ({ ...prev, modules: true }));
            try {
                const data = await getModules();
                setModules(data);
            } catch (err) {
                setError('Gagal memuat daftar modul');
            } finally {
                setLoading(prev => ({ ...prev, modules: false }));
            }
        };
        fetchModules();
    }, []);

    // Fetch groups when module is selected
    useEffect(() => {
        const fetchGroups = async () => {
            if (!selectedModule) {
                setGroups([]);
                return;
            }
            setLoading(prev => ({ ...prev, groups: true }));
            try {
                const data = await getGroups(selectedModule);
                setGroups(data);
            } catch (err) {
                setError('Gagal memuat daftar grup');
            } finally {
                setLoading(prev => ({ ...prev, groups: false }));
            }
        };
        fetchGroups();
    }, [selectedModule]);

    // Fetch tests when group is selected
    useEffect(() => {
        const fetchTests = async () => {
            if (!selectedGroup) {
                setTests([]);
                return;
            }
            setLoading(prev => ({ ...prev, tests: true }));
            try {
                const data = await getTests(selectedGroup);
                setTests(data);
            } catch (err) {
                setError('Gagal memuat daftar tes');
            } finally {
                setLoading(prev => ({ ...prev, tests: false }));
            }
        };
        fetchTests();
    }, [selectedGroup]);

    // Handle module change
    const handleModuleChange = (event) => {
        setSelectedModule(event.target.value);
        setSelectedGroup('');
        setSelectedTest('');
    };

    // Handle group change
    const handleGroupChange = (event) => {
        setSelectedGroup(event.target.value);
        setSelectedTest('');
    };

    // Handle test change
    const handleTestChange = (event) => {
        setSelectedTest(event.target.value);
    };

    // Handle download
    const handleDownload = () => {
        if (!selectedTest || !selectedGroup) {
            setError('Silakan pilih Kegiatan Penilaian, Kelas atau grup, dan nama mapel tes terlebih dahulu');
            return;
        }
        downloadExcel(selectedTest, selectedGroup);
    };

    // Handle download Module ZIP
    const handleDownloadModuleZip = () => {
        if (!selectedModule) {
            setError('Silakan pilih kegiatan penilaian terlebih dahulu');
            return;
        }
        downloadModuleZip(selectedModule);
    };

    // Handle download Group ZIP
    const handleDownloadGroupZip = () => {
        if (!selectedGroup) {
            setError('Silakan pilih kelas atau grup terlebih dahulu');
            return;
        }
        downloadGroupZip(selectedGroup);
    };

    // Handle error close
    const handleErrorClose = () => {
        setError(null);
    };

    return (
        <Container maxWidth="sm">
            <Box sx={{ mt: 4, mb: 4 }}>
                <Typography variant="h3" component="h2" gutterBottom align="center" sx={{ color: '#6a4fff', fontWeight: 'bold' }}>
                    Hi, Teacher!
                </Typography>
                <Paper elevation={3} sx={{ p: 4 }}>
                    <Typography variant="h4" component="h1" gutterBottom align="center">
                        Download Laporan Nilai Ujian
                    </Typography>

                    <Box sx={{ mt: 4 }}>
                        <FormControl fullWidth sx={{ mb: 3 }}>
                            <InputLabel>Kegiatan Penilaian</InputLabel>
                            <Select
                                value={selectedModule}
                                onChange={handleModuleChange}
                                label="Modul"
                                disabled={loading.modules}
                            >
                                <MenuItem value="">
                                    <em>Pilih Kegiatan Penilaian</em>
                                </MenuItem>
                                {modules.map((module) => (
                                    <MenuItem key={module.module_id} value={module.module_id}>
                                        {module.module_name}
                                    </MenuItem>
                                ))}
                            </Select>
                            {loading.modules && (
                                <CircularProgress size={24} sx={{ position: 'absolute', right: 40, top: 20 }} />
                            )}
                        </FormControl>

                        <FormControl fullWidth sx={{ mb: 3 }}>
                            <InputLabel>Kelas atau Group</InputLabel>
                            <Select
                                value={selectedGroup}
                                onChange={handleGroupChange}
                                label="Grup"
                                disabled={!selectedModule || loading.groups}
                            >
                                <MenuItem value="">
                                    <em>Pilih Kelas atau Group</em>
                                </MenuItem>
                                {groups.map((group) => (
                                    <MenuItem key={group.group_id} value={group.group_id}>
                                        {group.group_name}
                                    </MenuItem>
                                ))}
                            </Select>
                            {loading.groups && (
                                <CircularProgress size={24} sx={{ position: 'absolute', right: 40, top: 20 }} />
                            )}
                        </FormControl>

                        <FormControl fullWidth sx={{ mb: 4 }}>
                            <InputLabel>Mapel Tes</InputLabel>
                            <Select
                                value={selectedTest}
                                onChange={handleTestChange}
                                label="Tes"
                                disabled={!selectedGroup || loading.tests}
                            >
                                <MenuItem value="">
                                    <em>Pilih Mapel Tes</em>
                                </MenuItem>
                                {tests.map((test) => (
                                    <MenuItem key={test.test_id} value={test.test_id}>
                                        {test.test_name}
                                    </MenuItem>
                                ))}
                            </Select>
                            {loading.tests && (
                                <CircularProgress size={24} sx={{ position: 'absolute', right: 40, top: 20 }} />
                            )}
                        </FormControl>

                        <Button
                            variant="contained"
                            color="primary"
                            fullWidth
                            size="large"
                            onClick={handleDownload}
                            disabled={!selectedTest || !selectedGroup}
                            sx={{ mb: 2 }}
                        >
                            Download Excel Test
                        </Button>

                        <Button
                            variant="contained"
                            color="secondary"
                            fullWidth
                            size="large"
                            onClick={handleDownloadModuleZip}
                            disabled={!selectedModule}
                            sx={{ mb: 2 }}
                        >
                            Download ZIP Modul
                        </Button>

                        <Button
                            variant="contained"
                            color="secondary"
                            fullWidth
                            size="large"
                            onClick={handleDownloadGroupZip}
                            disabled={!selectedGroup}
                        >
                            Download ZIP Grup
                        </Button>
                    </Box>
                </Paper>
            </Box>

            <Snackbar
                open={!!error}
                autoHideDuration={6000}
                onClose={handleErrorClose}
                anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
            >
                <Alert onClose={handleErrorClose} severity="error" sx={{ width: '100%' }}>
                    {error}
                </Alert>
            </Snackbar>
            <Box sx={{ textAlign: 'center', mt: 4, mb: 4, color: '#888', fontSize: '0.875rem' }}>
                &copy; {new Date().getFullYear()}{' '}
                <a href="https://github.com/ftrhost" target="_blank" rel="noopener noreferrer" style={{ color: '#888', textDecoration: 'underline' }}>
                    mansaba media
                </a>. All rights reserved.
            </Box>
        </Container>
    );
}

export default DownloadForm; 