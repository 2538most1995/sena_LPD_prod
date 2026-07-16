import { createTheme } from '@mui/material/styles';

const fontFamily = "'Noto Sans Thai', Tahoma, system-ui, sans-serif";

const base = {
    typography: {
        fontFamily,
        fontSize: 15,
        button: { fontFamily, fontSize: 14, fontWeight: 700, textTransform: 'none' },
        h1: { fontFamily, fontWeight: 800, letterSpacing: '-0.03em' },
        h2: { fontFamily, fontWeight: 750, letterSpacing: '-0.015em' },
    },
    shape: { borderRadius: 12 },
    components: {
        MuiButton: {
            defaultProps: { disableRipple: true },
            styleOverrides: {
                root: {
                    minHeight: 44,
                    paddingInline: 16,
                    borderRadius: 11,
                    boxShadow: 'none',
                    transition: 'transform 140ms cubic-bezier(0.23, 1, 0.32, 1), background-color 160ms ease, border-color 160ms ease, color 160ms ease',
                    '&:active': { transform: 'scale(0.97)' },
                },
            },
        },
        MuiIconButton: {
            defaultProps: { disableRipple: true },
            styleOverrides: {
                root: {
                    borderRadius: 11,
                    transition: 'transform 140ms cubic-bezier(0.23, 1, 0.32, 1), background-color 160ms ease, color 160ms ease',
                    '&:active': { transform: 'scale(0.94)' },
                },
            },
        },
        MuiOutlinedInput: {
            styleOverrides: {
                root: { borderRadius: 11, backgroundImage: 'none' },
                input: { paddingBlock: 12.5 },
            },
        },
        MuiCard: { styleOverrides: { root: { backgroundImage: 'none' } } },
        MuiDialog: { defaultProps: { transitionDuration: { enter: 220, exit: 150 } } },
        MuiDialogTitle: { styleOverrides: { root: { padding: '24px 24px 12px', fontSize: 22, fontWeight: 800 } } },
        MuiDialogContent: { styleOverrides: { root: { padding: '12px 24px 24px' } } },
        MuiDialogActions: { styleOverrides: { root: { padding: '16px 24px 20px' } } },
        MuiChip: { styleOverrides: { root: { fontWeight: 700, borderRadius: 8 } } },
        MuiTab: { styleOverrides: { root: { minHeight: 42, borderRadius: 9, fontWeight: 700, textTransform: 'none' } } },
    },
};

export const senaLightTheme = createTheme({
    ...base,
    palette: {
        mode: 'light',
        primary: { main: '#6d3aa8', dark: '#51267f', light: '#e9dcf7', contrastText: '#fffaff' },
        secondary: { main: '#e4b72f', dark: '#a97800', light: '#fff2b8', contrastText: '#352400' },
        background: { default: '#f7f3fa', paper: '#fffcff' },
        text: { primary: '#271c31', secondary: '#746a7d' },
        divider: '#e2d9e8',
        success: { main: '#2f7d5c' },
        warning: { main: '#a96f12' },
        error: { main: '#bb3e4a' },
        info: { main: '#397da0' },
    },
});

export const senaDarkTheme = createTheme({
    ...base,
    palette: {
        mode: 'dark',
        primary: { main: '#c7a4f2', dark: '#a87adb', light: '#3a2850', contrastText: '#21132e' },
        secondary: { main: '#f4cf5a', dark: '#c69d22', light: '#493c16', contrastText: '#261b00' },
        background: { default: '#17111d', paper: '#21182a' },
        text: { primary: '#f8f2fc', secondary: '#b9adbf' },
        divider: '#44364e',
        success: { main: '#68bd91' },
        warning: { main: '#e1ae54' },
        error: { main: '#e27882' },
        info: { main: '#6ab0d0' },
    },
});
