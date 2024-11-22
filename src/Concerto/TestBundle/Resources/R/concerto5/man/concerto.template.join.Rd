\name{leap.template.join}
\alias{leap.template.join}
\title{
    Template helper function.
}
\description{
    Combines template CSS, JS and HTML.
}
\usage{
    leap.template.join(html="", css="", js="", templateId=NULL, params = list())
}
\arguments{
    \item{html}{
        HTML
    }
    \item{css}{
        CSS
    }
    \item{js}{
        javascript
    }
    \item{templateId}{
        Template id.
    }
    \item{params}{
        Named list with parameters.
    }
}
\details{
}
\value{
    Combined HTML.
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    html <- leap.template.join(templateId=1)
}

\keyword{template}
\keyword{HTML}
