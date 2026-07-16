import React from 'react';
import { MessageBar, MessageBarBody } from '../ui';

export function ErrorMessage({ message }) {
    if (!message) return null;
    return <MessageBar intent="error"><MessageBarBody><span className="multiline-message">{message}</span></MessageBarBody></MessageBar>;
}

export function SuccessMessage({ message }) {
    if (!message) return null;
    return <MessageBar intent="success"><MessageBarBody>{message}</MessageBarBody></MessageBar>;
}
