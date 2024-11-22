\name{leap.template.loader}
\alias{leap.template.loader}
\title{
    Loader template.
}
\description{
    Sets a template that will be shown when the proper templates are loaded.
}
\usage{
    leap.template.loader(templateId=-1, html="", head="", params = list())
}

\arguments{
    \item{templateId}{
        Template ID or name.
    }
    \item{html}{
        Template html.
    }
    \item{head}{
        Template head tag.
    }
    \item{params}{
        Named list with parameters we want to be inserted into template HTML.
    }
}
\details{
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
    response1 <- leap.template.loader(1)
    response2 <- leap.template.loader(html="<div align="center">loading ... please wait ...</div>")
}

\keyword{template}
\keyword{html}
