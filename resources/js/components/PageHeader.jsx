import React from 'react';

export default function PageHeader({ eyebrow, title, description, actions }) {
    return (
        <header className="page-header">
            <div>
                {eyebrow ? <p className="page-eyebrow">{eyebrow}</p> : null}
                <h1>{title}</h1>
                {description ? <p>{description}</p> : null}
            </div>
            {actions ? <div className="page-actions">{actions}</div> : null}
        </header>
    );
}
