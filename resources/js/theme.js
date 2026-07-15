import { createTheme } from '@mui/material/styles';

const fontFamily = "'Noto Sans Thai', Tahoma, system-ui, sans-serif";

const base = {
    typography: {
        fontFamily,
        button: { fontFamily, fontWeight: 700, textTransform: 'none' },
        h1: { fontFamily, fontWeight: 800, letterSpacing: '-0.03em' },
        h2: { fontFamily, fontWeight: 750, letterSpacing: '-0.015em' },
    },
    shape: { borderRadius: 12 },
    components: {
        MuiButton: {
            defaultProps: { disableRipple: true },
            styleOverrides: {
                root: {
                    minHeight: 40,
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
                input: { paddingBlock: 11.5 },
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
        primary: { main: '#0f766e', dark: '#0b5b56', light: '#d9f1ed', contrastText: '#f7fffd' },
        secondary: { main: '#273b43' },
        background: { default: '#f2f6f6', paper: '#fbfdfd' },
        text: { primary: '#17272d', secondary: '#66777d' },
        divider: '#dce5e6',
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
        primary: { main: '#5bc1b4', dark: '#35a99a', light: '#163d3b', contrastText: '#102022' },
        secondary: { main: '#b8c8cc' },
        background: { default: '#11191d', paper: '#182328' },
        text: { primary: '#edf5f5', secondary: '#9fb0b5' },
        divider: '#304046',
        success: { main: '#68bd91' },
        warning: { main: '#e1ae54' },
        error: { main: '#e27882' },
        info: { main: '#6ab0d0' },
    },
});
